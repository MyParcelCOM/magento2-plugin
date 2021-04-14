<?php

namespace MyParcelCOM\Magento\Adapter;

class MpShop extends MpAdapter
{
    public function getShops()
    {
        return $this->getApi()->getShops();
    }

    public function getDefaultShop()
    {
        return $this->getApi()->getDefaultShop();
    }
}
