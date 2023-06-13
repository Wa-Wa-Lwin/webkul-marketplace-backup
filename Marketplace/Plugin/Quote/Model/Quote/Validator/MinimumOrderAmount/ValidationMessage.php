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
namespace Webkul\Marketplace\Plugin\Quote\Model\Quote\Validator\MinimumOrderAmount;

use Webkul\Marketplace\Helper\Data as MarketplaceHelperData;
use Webkul\Marketplace\Model\SaleperpartnerFactory as MpSalesPartner;

class ValidationMessage
{
    /**
     * @var MarketplaceHelperData
     */
    protected $marketplaceHelperData;

    /**
     * @var MpSalesPartner
     */
    protected $mpSalesPartner;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;

    /**
     * @param MarketplaceHelperData $marketplaceHelperData
     * @param MpSalesPartner $mpSalesPartner
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     */
    public function __construct(
        MarketplaceHelperData $marketplaceHelperData,
        MpSalesPartner $mpSalesPartner,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Pricing\Helper\Data $priceHelper
    ) {
        $this->marketplaceHelperData = $marketplaceHelperData;
        $this->mpSalesPartner = $mpSalesPartner;
        $this->checkoutSession = $checkoutSession;
        $this->priceHelper = $priceHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function aroundGetMessage(
        \Magento\Quote\Model\Quote\Validator\MinimumOrderAmount\ValidationMessage $subject,
        callable $proceed
    ) {
        $helper = $this->marketplaceHelperData;
        $count = 1;
        $message = '';
        if ($helper->getMinOrderSettings()) {
            $items = $this->checkoutSession->getQuote()->getAllItems();
            $sellerDetails = $helper->getSellerItemsDetails($items);
            foreach ($sellerDetails as $sellerId => $amount) {
                list($status, $minOrderAmount) = $this->minOrderStatus($sellerId, $amount);
                if ($status) {
                    $minimumAmount = $this->priceHelper->currency(
                        $minOrderAmount,
                        true,
                        false
                    );
                    if ($sellerId) {
                        $rowsocial = $helper->getSellerDataBySellerId($sellerId);
                        $shoptitle = '';
                        foreach ($rowsocial as $value) {
                            $shoptitle = $value['shop_title'];
                            if (!$shoptitle) {
                                $shoptitle = $value->getShopUrl();
                            }
                        }
                    } else {
                        $shoptitle = $helper->getAdminName();
                    }
                    if ($count > 1) {
                        $message .= __(' & %1 product(s) is %2', $shoptitle, $minimumAmount);
                    } else {
                        $message = __('Minimum order amount for %1 product(s) is %2', $shoptitle, $minimumAmount);
                    }
                    $count ++;
                }
            }
            return $message;
        }
        return $proceed();
    }
    /**
     * minOrderStatus function
     *
     * @param int $sellerId
     * @param float $amount
     * @return mixed[]
     */
    private function minOrderStatus($sellerId, $amount)
    {
        $status = false;
        $minOrderAmount = 0;
        $helper = $this->marketplaceHelperData;
        if ($sellerId) {
            $salePerPartnerModel = $this->mpSalesPartner->create()
                                    ->getCollection()
                                    ->addFieldToFilter('seller_id', $sellerId)
                                    ->addFieldToFilter('min_order_status', 1);
            if ($salePerPartnerModel->getSize()) {
                $minOrderAmount = $salePerPartnerModel->getFirstItem()
                                                ->getMinOrderAmount();
                if ($minOrderAmount > $amount) {
                    $status = true;
                }
            } elseif ($helper->getMinAmountForSeller()) {
                $minOrderAmount = $helper->getMinOrderAmount();
                if ($minOrderAmount > $amount) {
                    $status = true;
                }
            }
        } else {
            $minOrderAmount = $helper->getMinOrderAmount();
            if ($minOrderAmount > $amount) {
                $status = true;
            }
        }
        return [$status, $minOrderAmount];
    }
}
