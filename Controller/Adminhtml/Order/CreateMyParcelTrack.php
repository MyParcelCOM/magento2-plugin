<?php

namespace MyParcelCOM\Magento\Controller\Adminhtml\Order;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Exception\LocalizedException;
use MyParcelCOM\Magento\Model\Sales\MyParcelOrderCollection;
use Throwable;

class CreateMyParcelTrack extends Action
{
    const PATH_URI_ORDER_INDEX = 'sales/order/index';

    /** @var MyParcelOrderCollection */
    private $orderCollection;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);

        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->orderCollection = new MyParcelOrderCollection(
            $context->getObjectManager(),
            $this->getRequest()
        );
    }

    /**
     * Execute action based on request and return result
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->massAction();

        return $this->resultRedirectFactory->create()->setPath(self::PATH_URI_ORDER_INDEX);
    }

    /**
     * Get selected items and process them
     *
     * @return $this
     * @throws LocalizedException
     */
    private function massAction()
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
            ->setNewMagentoShipment();

        if (!$this->orderCollection->hasShipment()) {
            $this->messageManager->addErrorMessage(__(MyParcelOrderCollection::ERROR_ORDER_HAS_NO_SHIPMENT));
            return $this;
        }
        $orderIncrementIds = $this->orderCollection->getIncrementIds();

        try {
            $this->orderCollection
                ->setMagentoTrack()
                ->createShipmentConcepts()
                ->updateGridByOrder();

            $this->messageManager->addSuccessMessage(sprintf(__(MyParcelOrderCollection::SUCCESS_SHIPMENT_CREATED), implode(', ', $orderIncrementIds)));
        } catch (Throwable $e) {
            $this->messageManager->addErrorMessage(__(MyParcelOrderCollection::ERROR_SHIPMENT_CREATE_FAIL) . ': ' . $e->getMessage());
        }

        return $this;
    }
}
