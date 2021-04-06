<?php

namespace MyParcelCOM\Magento\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class TrackNumber extends Column
{
    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (key_exists('track_number', $item)) {
                    $item[$this->getData('name')] = $item['track_number'];
                }
            }
        }

        return $dataSource;
    }
}
