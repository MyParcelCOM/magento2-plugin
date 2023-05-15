<?php

namespace MyParcelCOM\Magento\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use MyParcelCOM\Magento\Http\MyParcelComApi;
use Throwable;

class Shop implements ArrayInterface
{
    private array $options = [];

    public function __construct()
    {
        try {
            $api = (new MyParcelComApi())->getInstance();
            /** @var \MyParcelCom\ApiSdk\Resources\Shop $shops */
            $shops = $api->getShops();
            foreach ($shops as $shop) {
                $this->options[] = [
                    'value' => $shop->getId(),
                    'label' => $shop->getName(),
                ];
            }
            uasort($this->options, function ($a, $b) {
                return (strtolower($a['label']) < strtolower($b['label'])) ? -1 : 1;
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

    public function toOptionArray(): array
    {
        return $this->options;
    }

    public function toArray(): array
    {
        $array = [];
        foreach ($this->options as $option) {
            $array[$option['value']] = $option['label'];
        }
        return $array;
    }
}
