<?php

namespace MyParcelCOM\Magento\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class TrackStatus extends Column
{
    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['track_status'])) {
                    $item[$this->getData('name')] = implode('<br/>', [
                        __($item['track_status']),
                        'Exported to <a href="https://app.myparcel.com" target="_blank">MyParcel.com</a>',
                    ]);
                }
            }
        }

        return $dataSource;
    }
}
