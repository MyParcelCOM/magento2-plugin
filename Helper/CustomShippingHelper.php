<?php
/**
 * MagePal_AdminSalesOrderViewButton Magento component
 *
 * @category    MagePal
 * @package     MagePal_AdminSalesOrderViewButton
 * @author      MagePal Team <info@magepal.com>
 * @copyright   MagePal (http://www.magepal.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace MyParcelCOM\Magento\Helper;

use MyParcelCOM\Magento\Model\Carrier\MyParcelCarrier;

class CustomShippingHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function getShippingType($method)
    {
        /**
         * Get configurations from serialized component
        **/
        /*$arrayValues = [];
        $configData = $this->getConfigData('shipping_type');

        if (is_string($configData) && !empty($configData) && $configData !== '[]') {
            if ($this->isJson($configData)) {
                $arrayValues = json_decode($configData, true);
            } else {
                $arrayValues = array_values(unserialize($configData));
            }
        }

        return (array)$arrayValues;*/

        if ($this->isEnabled($method)) {
            return [
                'code'  => $method,
                'title' => $this->getConfigData('title', $method),
                'price' => $this->getConfigData('price', $method, 0)
            ];
        }

        return null;
    }

    /**
     * @param   string $code ("pickup" | "delivery")
     * @return bool
     */
    public function isEnabled($code)
    {
        return (bool)$this->getConfigData('active', $code);
    }

    /**
     * Retrieve information from carrier configuration
     *
     * @param   string $field
     * @param   string $code ("pickup" | "delivery")
     * @return  mixed
     */
    public function getConfigData($field, $code, $default = '')
    {
        if (empty($code)) {
            return false;
        }

        $path = 'carriers/' . $code . '/' . $field;

        $configValue = $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (empty($configValue)) {
            return $default;
        }

        return $configValue;
    }

    /**
     * Magento 2.2 return json instead of serialize array
     *
     * @param   string $string
     * @return  bool
     */
    public function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
