<?php

namespace MyParcelCOM\Magento\Controller\Adminhtml\Order;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResponseInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use MyParcelCOM\Magento\Adapter\MpShipment;
use MyParcelCOM\Magento\Model\Sales\MyParcelOrderCollection;

class CreateMyParcelTrack extends \Magento\Framework\App\Action\Action
{
    const PATH_MODEL_ORDER = 'Magento\Sales\Model\Order';
    const PATH_URI_ORDER_INDEX = 'sales/order/index';

    /**
     * @var MyParcelOrderCollection
     */
    private $orderCollection;

    /**
     * CreateAndPrintMyParcelTrack constructor.
     *
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
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws LocalizedException
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

        $this->orderCollection->addOrdersToCollection($orderIds);

        $this->orderCollection
            ->setNewMagentoShipment();

        if (!$this->orderCollection->hasShipment()) {
            $this->messageManager->addErrorMessage(__(MyParcelOrderCollection::ERROR_ORDER_HAS_NO_SHIPMENT));
            return $this;
        }

        try {
            $this->orderCollection
                ->setMagentoTrack()
                ->createShipmentConcepts()
                ->updateGridByOrder();

            $this->messageManager->addSuccessMessage(sprintf(__(MyParcelOrderCollection::SUCCESS_SHIPMENT_CREATED), implode(', ', $orderIds)));

        } catch (\Throwable  $e) {
            $this->messageManager->addErrorMessage(__(MyParcelOrderCollection::ERROR_SHIPMENT_CREATE_FAIL . ': ' . $e->getMessage()));
            return $this;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__(MyParcelOrderCollection::ERROR_SHIPMENT_CREATE_FAIL . ': ' . $e->getMessage()));
        }

       return $this;
    }
}
