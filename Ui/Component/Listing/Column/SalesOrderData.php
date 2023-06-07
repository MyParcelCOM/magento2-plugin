<?php

namespace MyParcelCOM\Magento\Ui\Component\Listing\Column;

use Magento\Framework\App\ObjectManager;
use Magento\Ui\Component\Listing\Columns\Column;
use MyParcelCOM\Magento\Model\Data;
use MyParcelCOM\Magento\Model\ResourceModel\Data as DataResource;

class SalesOrderData extends Column
{
    public function prepareDataSource(array $dataSource): array
    {
        $objectManager = ObjectManager::getInstance();

        /** @var DataResource $dataResource */
        $dataResource = $objectManager->create(DataResource::class);

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                /** @var Data $data */
                $data = $objectManager->create(Data::class);
                $dataResource->load($data, $item['entity_id'], 'order_id');

                $item[$this->getData('name')] = implode('', [
                    '<span title="' . $data->getStatusCode() . '">' . $data->getStatusName() . '</span>',
                    '<br/>',
                    '<a href="' . $data->getTrackingUrl() . '" target="_blank">',
                    $data->getTrackingCode(),
                    '</a>',
                ]);
            }
        }

        return $dataSource;
    }
}
