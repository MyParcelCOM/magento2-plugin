<?php

namespace MyParcelCOM\Magento\Block\Adminhtml\System\Config;

use Magento\Framework\View\Element\Template\Context;

class Data extends \Magento\Framework\View\Element\Template
{
    public function getAjaxUrl()
    {
        return $this->_urlBuilder->getUrl('myparcelcom/system/Config');
    }
}
