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
                    __(MyParcelOrderCollection::SUCCESS_SHIPMENT_CREATED),
                    implode(', ', $this->orderCollection->getIncrementIds())
                ));
            } catch (Throwable $e) {
                $this->messageManager->addErrorMessage(__(MyParcelOrderCollection::ERROR_SHIPMENT_CREATE_FAIL) . ': ' . $e->getMessage());
            }
        } else {
            $this->messageManager->addErrorMessage(__(MyParcelOrderCollection::ERROR_ORDER_HAS_NO_SHIPMENT));
        }

        return $this->resultRedirectFactory->create()->setPath('sales/order/index');
    }
}
