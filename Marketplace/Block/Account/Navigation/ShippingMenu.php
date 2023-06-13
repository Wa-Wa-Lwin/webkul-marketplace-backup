<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\Marketplace\Block\Account\Navigation;

/**
 * Marketplace Navigation link
 *
 */
class ShippingMenu extends \Webkul\Marketplace\Block\Account\Navigation
{
    /**
     * isShippineAvlForSeller
     * @return boolean
     */
    public function isShippineAvlForSeller()
    {
        $activeCarriers = $this->shipconfig->getActiveCarriers();
        $status = false;
        foreach ($activeCarriers as $carrierCode => $carrierModel) {
            $allowToSeller = $this->_scopeConfig->getValue(
                'carriers/'.$carrierCode.'/allow_seller'
            );
            if ($allowToSeller) {
                $status = true;
            }
        }
        return $status;
    }
}
