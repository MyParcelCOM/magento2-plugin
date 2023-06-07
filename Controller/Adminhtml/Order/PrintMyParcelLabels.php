<?php

namespace MyParcelCOM\Magento\Controller\Adminhtml\Order;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use MyParcelCom\ApiSdk\LabelCombiner;
use MyParcelCom\ApiSdk\LabelCombinerInterface;
use MyParcelCom\ApiSdk\Resources\File;
use MyParcelCom\ApiSdk\Resources\Interfaces\FileInterface;
use MyParcelCOM\Magento\Http\MyParcelComApi;
use MyParcelCOM\Magento\Model\Data;
use MyParcelCOM\Magento\Model\ResourceModel\Data\Collection as DataCollection;

class PrintMyParcelLabels extends Action
{
    protected DateTime $dateTime;
    protected FileFactory $fileFactory;

    public function __construct(Context $context)
    {
        parent::__construct($context);

        $this->dateTime = $context->getObjectManager()->get(DateTime::class);
        $this->fileFactory = $context->getObjectManager()->get(FileFactory::class);
    }

    /**
     * @return ResponseInterface
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
            $this->messageManager->addErrorMessage('No orders selected');

            return $this->resultRedirectFactory->create()->setPath('sales/order/index');
        }

        /** @var Data $data */
        /** @var DataCollection $dataCollection */
        $dataCollection = ObjectManager::getInstance()->create(DataCollection::class);
        $collection = $dataCollection->addFieldToFilter('order_id', ['in' => $orderIds]);

        // TODO: The code below uses separate requests to get shipments and files. The SDK needs to be updated.
        $api = (new MyParcelComApi())->getInstance();

        $shipments = [];
        foreach ($collection->getItems() as $data) {
            $shipments[] = $api->getShipment($data->getShipmentId());
        }
        $shipments = array_filter($shipments);

        /** @var File[] $files */
        $files = [];
        foreach ($shipments as $shipment) {
            $files = array_merge($files, $shipment->getFiles(FileInterface::DOCUMENT_TYPE_LABEL));
        }

        if (empty($files)) {
            $this->messageManager->addErrorMessage('No files available to be printed. Are the shipments successfully registered?');

            return $this->resultRedirectFactory->create()->setPath('sales/order/index');
        }

        $labelCombiner = new LabelCombiner();
        $combinedFile = $labelCombiner->combineLabels($files, LabelCombinerInterface::PAGE_SIZE_A6);
        $format = $files[0]->getFormats()[0];

        return $this->fileFactory->create(
            sprintf('myparcelcom-labels-%s.' . $format['extension'], $this->dateTime->date('Y-m-d_H-i-s')),
            ['type' => 'string', 'value' => $combinedFile->getStream()->getContents(), 'rm' => true],
            DirectoryList::VAR_DIR,
            $format['mime_type']
        );
    }
}
