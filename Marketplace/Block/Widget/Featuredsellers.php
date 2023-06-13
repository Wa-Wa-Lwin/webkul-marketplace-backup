<?php
/**
 * webkul
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\Marketplace\Block\Widget;

use Webkul\Marketplace\Model\SellerFactory;
use Magento\Framework\View\Element\Template\Context;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Magento\Framework\View\Asset\Repository;

class Featuredsellers extends \Magento\Framework\View\Element\Template implements \Magento\Widget\Block\BlockInterface
{
    /**
     * SellerFactory
     */
    protected $sellerFactory;

    /**
     * Context
     */
    protected $context;

    /**
     * MpHelper
     */
    protected $mphelper;

    /**
     * Repository
     */
    protected $assetRepository;

    /**
     * construct
     * @param Context $context
     * @param SellerFactory $sellerFactory
     * @param MpHelper $mphelper
     * @param Repository $assetRepository
     */
    public function __construct(
        Context $context,
        SellerFactory $sellerFactory,
        MpHelper $mphelper,
        Repository $assetRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->mphelper = $mphelper;
        $this->assetRepository = $assetRepository;
        $this->sellerFactory = $sellerFactory;
    }

    /**
     * [getAllowedStatus is used to check the status of featured seller slider]
     * @return bool
     */
    public function getAllowedStatus()
    {
        $helper = $this->mphelper;
        $profileDisplayFlag = $helper->getSellerProfileDisplayFlag();
        $featuredSellerFlag = $helper->getConfigValue('profile_settings', 'vendor_featured');
        if ($profileDisplayFlag && $featuredSellerFlag) {
            return true;
        }
        return false;
    }

    /**
     * set the template file
     */
    public function _toHtml()
    {
        if ($this->getAllowedStatus()) {
            $this->setTemplate('featuredsellers.phtml');
        }
        return parent::_toHtml();
    }

    /**
     * getAssetUrl to include css file in phtml.
     * @param $asset
     * @return $asseturl
     */
    public function getAssetUrl($asset)
    {
        return $this->assetRepository->createAsset($asset)->getUrl();
    }

    /**
     * Get seller Ids
     * @return array
     */
    public function getSellerIds()
    {
        $sellerIds = $this->getData('sellerids');
        $sellerIdsArray = explode(',', $sellerIds);
        return $sellerIdsArray;
    }

    /**
     * [getSellerDetailsById get selller details by the sellerids]
     * @return array
     */
    public function getSellerDetailsById()
    {
        $sellerDetails =[];
        $helper= $this->mphelper;
        $sellerIds = $this->getSellerIds();
        $storeId = $helper->getCurrentStoreId();
        $collection = $this->sellerFactory->create()
                        ->getCollection()
                        ->addFieldToFilter('is_seller', \Webkul\Marketplace\Model\Seller::STATUS_ENABLED)
                        ->addFieldToFilter(
                            ['store_id','store_id'],
                            [
                                 ['eq'=>$storeId],
                                 ['eq'=>0]
                             ]
                        )
                        ->addFieldToFilter('seller_id', ['in'=>$sellerIds]);
        foreach ($collection as $sellerData) {
            $shopTitle = $sellerData->getShopTitle() ? $sellerData->getShopTitle() : $sellerData->getShopUrl();
            $sellerDetails[$sellerData->getSellerId()] = [
            'logo_pic' => $sellerData->getLogoPic(),
            'shop_url' => $sellerData->getShopUrl(),
            'shop_title' => $shopTitle
            ];
        }
        return $sellerDetails;
    }

    /**
     * [getLogoUrl get seller logo image path]
     * @return string
     */
    public function getLogoUrl()
    {
        return $this->mphelper->getMediaUrl().'avatar/';
    }

    /**
     * [getTransitionTime used to get the Transition time]
     * @return string
     */
    public function getTransitionTime()
    {
        return $this->getData('transitionTime');
    }

    /**
     * [getSliderWidth used to get the slider width]
     * @return string
     */
    public function getSliderWidth()
    {
        return $this->getData('sliderwidth');
    }

    /**
     * [getImageHeight used to get the seller logo height]
     * @return string
     */
    public function getImageHeight()
    {
        return $this->getData('imageheight');
    }
}
