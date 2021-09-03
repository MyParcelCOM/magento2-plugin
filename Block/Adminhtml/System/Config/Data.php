<?php

namespace MyParcelCOM\Magento\Block\Adminhtml\System\Config;

use Magento\Framework\View\Element\Template;

class Data extends Template
{
    public function getAjaxUrl()
    {
        return $this->_urlBuilder->getUrl('myparcelcom/system/Config');
    }
}
