<?php

namespace MyParcelCOM\Magento\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use MyParcelCOM\Magento\Http\MyParcelComApi;
use Throwable;

class Shop implements ArrayInterface
{
    private $options = [];

    public function __construct()
    {
        try {
            $api = (new MyParcelComApi())->getInstance();
            $shops = $api->getShops();
            foreach ($shops as $shop) {
                $this->options[] = [
                    'value' => $shop->getId(),
                    'label' => $shop->getName(),
                ];
            }
            uasort($this->options, function ($a, $b) {
                return strtolower($a['label']) > strtolower($b['label']);
            });
        } catch (Throwable $throwable) {
            $this->options[] = [
                'value' => null,
                'label' => ($throwable->getCode() === 401)
                    ? 'Please enter and save your Client ID and Client Secret first'
                    : $throwable->getMessage(),
            ];
        }
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->options;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = [];
        foreach ($this->options as $option) {
            $array[$option['value']] = $option['label'];
        }
        return $array;
    }
}
