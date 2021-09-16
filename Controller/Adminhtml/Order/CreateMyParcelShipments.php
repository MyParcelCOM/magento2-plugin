<?php

namespace MyParcelCOM\Magento\Controller\Adminhtml\Order;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use MyParcelCOM\Magento\Model\Sales\MyParcelOrderCollection;
use Throwable;

class CreateMyParcelShipments extends Action
{
    /** @var MyParcelOrderCollection */
    private $orderCollection;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);

        $this->orderCollection = new MyParcelOrderCollection($context->getObjectManager());
    }

    /**
     * @return ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('selected_ids')) {
            $orderIds = explode(',', $this->getRequest()->getParam('selected_ids'));
        } else {
            $orderIds = $this->getRequest()->getParam('selected');
        }

        if (empty($orderIds)) {
            throw new LocalizedException(__('No items selected'));
        }

        $this->orderCollection
            ->addOrdersToCollection($orderIds)
            ->createMagentoShipments();

        if ($this->orderCollection->hasShipment()) {
            try {
                $this->orderCollection
                    ->setMagentoTrack()
                    ->createMyParcelShipments();

                $this->messageManager->addSuccessMessage(sprintf(
                    'Selected shipments from orders: %s have been created at MyParcel.com',
                    implode(', ', $this->orderCollection->getIncrementIds())
                ));
            } catch (Throwable $e) {
                $this->messageManager->addErrorMessage(implode(' ', [
                    'Some of the selected shipments have not been created.',
                    $e->getMessage(),
                ]));
            }
        } else {
            $this->messageManager->addErrorMessage(implode(' ', [
                'No shipment can be made for this order.',
                'Shipments cannot be created if the status is On Hold or if the product is digital.',
            ]));
        }

        return $this->resultRedirectFactory->create()->setPath('sales/order/index');
    }
}
