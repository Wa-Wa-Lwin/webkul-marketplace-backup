<?php
/**
 * Webkul Software
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\Marketplace\ViewModel;

use Webkul\Marketplace\Model\SaleslistFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;

class Profile implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * SaleslistFactory
     *
     * @var SaleslistFactory
     */
    public $saleslistFactory;

    /**
     * MpHelper
     *
     * @var MpHelper
     */
    public $mpHelper;

    /**
     * @param SaleslistFactory $saleslistFactory
     * @param MpHelper $mpHelper
     */
    public function __construct(
        SaleslistFactory $saleslistFactory,
        MpHelper $mpHelper
    ) {
        $this->saleslistFactory = $saleslistFactory;
        $this->mpHelpder = $mpHelper;
    }

    /**
     * getUserInfo function
     *
     * @param int $productId
     * @param int $orderId
     * @return int
     */
    public function getUserInfo($productId, $orderId)
    {
        $sellerId = 0;
        $marketplaceSalesCollection = $this->saleslistFactory->create()
        ->getCollection()
        ->addFieldToFilter(
            'mageproduct_id',
            ['eq' => $productId]
        )
        ->addFieldToFilter(
            'order_id',
            ['eq' => $orderId]
        );
        if (count($marketplaceSalesCollection)) {
            foreach ($marketplaceSalesCollection as $mpSales) {
                $sellerId = $mpSales->getSellerId();
            }
        }
        return $sellerId;
    }
    /**
     * getSellerDetails function
     *
     * @param int $sellerId
     * @return mixed[]
     */
    public function getSellerDetails($sellerId)
    {
        $rowsocial = $this->mpHelpder->getSellerDataBySellerId($sellerId);
        $shopTitle = '';
        $shopUrl = '';
        foreach ($rowsocial as $value) {
            $shopTitle = $value['shop_title'];
            $shopUrl = $value['shop_url'];
            if (!$shopTitle) {
                $shopTitle = $value->getShopUrl();
            }
        }
        return [$shopTitle, $shopUrl];
    }
    /**
     * getShopUrl function
     *
     * @param string $shopUrl
     * @return string
     */
    public function getShopUrl($shopUrl)
    {
        return $this->mpHelpder->getRewriteUrl(
            'marketplace/seller/profile/shop/'.$shopUrl
        );
    }
}
