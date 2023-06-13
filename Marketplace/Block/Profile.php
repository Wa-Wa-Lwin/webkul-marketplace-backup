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

namespace Webkul\Marketplace\Block;

/*
 * Webkul Marketplace Seller Profile Block
 */
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Catalog\Block\Product\AbstractProduct;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Model\FeedbackFactory;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;
use Webkul\Marketplace\Model\ProductFactory as MpProductModel;
use Magento\Catalog\Model\ProductFactory;
use Webkul\Marketplace\Model\ResourceModel\SellerFlagReason\CollectionFactory as SellerFlagReason;

class Profile extends AbstractProduct
{
    const FLAG_REASON_ENABLE = 1;
    const FLAG_REASON_DISABLE = 0;
    /**
     * @var \Magento\Framework\Data\Helper\PostHelper
     */
    protected $_postDataHelper;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $urlHelper;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $session;

    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $stringUtils;

    /**
     * @var MpHelper
     */
    protected $mpHelper;

    /**
     * @var FeedbackFactory
     */
    protected $feedbackModel;

    /**
     * @var CollectionFactory
     */
    protected $mpProductCollection;

    /**
     * @var MpProductModel
     */
    protected $mpProductModel;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\SellerFlagReason\Collection
     */
    protected $reasonCollection;

    /**
     * @param Context                                   $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Framework\Url\Helper\Data        $urlHelper
     * @param Customer                                  $customer
     * @param \Magento\Framework\Stdlib\StringUtils     $stringUtils
     * @param MpHelper                                  $mpHelper
     * @param FeedbackFactory                           $feedbackModel
     * @param CollectionFactory                         $mpProductCollection
     * @param MpProductModel                            $mpProductModel
     * @param ProductFactory                            $productFactory
     * @param array                                     $data
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        Customer $customer,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\Stdlib\StringUtils $stringUtils,
        MpHelper $mpHelper,
        FeedbackFactory $feedbackModel,
        CollectionFactory $mpProductCollection,
        MpProductModel $mpProductModel,
        ProductFactory $productFactory,
        array $data = [],
        SellerFlagReason $reasonCollection = null,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider = null
    ) {
        $this->_postDataHelper = $postDataHelper;
        $this->urlHelper = $urlHelper;
        $this->Customer = $customer;
        $this->Session = $session;
        $this->stringUtils = $stringUtils;
        $this->mpHelper = $mpHelper;
        $this->feedbackModel = $feedbackModel;
        $this->mpProductCollection = $mpProductCollection;
        $this->mpProductModel = $mpProductModel;
        $this->productFactory = $productFactory;
        $this->reasonCollection = $reasonCollection ?: \Magento\Framework\App\ObjectManager::getInstance()
                                  ->create(SellerFlagReason::class);
        $this->filterProvider = $filterProvider ?: \Magento\Framework\App\ObjectManager::getInstance()
        ->create(\Magento\Cms\Model\Template\FilterProvider::class);
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $partner = $this->getProfileDetail();
        if ($partner) {
            $title = $partner->getShopTitle();
            if (!$title) {
                $title = __('Marketplace Seller Profile');
            }
            $this->pageConfig->getTitle()->set($title);
            $description = $partner->getMetaDescription();
            if ($description) {
                $this->pageConfig->setDescription($description);
            } else {
                $this->pageConfig->setDescription(
                    $this->stringUtils->substr($partner->getCompanyDescription(), 0, 255)
                );
            }
            $keywords = $partner->getMetaKeywords();
            if ($keywords) {
                $this->pageConfig->setKeywords($keywords);
            }

            $pageMainTitle = $this->getLayout()->getBlock('page.main.title');
            if ($pageMainTitle && $title) {
                $pageMainTitle->setPageTitle($title);
            }

            $this->pageConfig->addRemotePageAsset(
                $this->_urlBuilder->getCurrentUrl(''),
                'canonical',
                ['attributes' => ['rel' => 'canonical']]
            );
        }

        return $this;
    }

    /**
     * Get Seller Profile Details
     *
     * @return \Webkul\Marketplace\Model\Seller | bool
     */
    public function getProfileDetail()
    {
        $helper = $this->mpHelper;
        return $helper->getProfileDetail(MpHelper::URL_TYPE_PROFILE);
    }

    public function getFeed()
    {
        $partner = $this->getProfileDetail();
        if ($partner) {
            return $this->mpHelper->getFeedTotal($partner->getSellerId());
        } else {
            return [];
        }
    }

    public function getFeedCollection()
    {
        $collection = [];
        $partner = $this->getProfileDetail();
        if ($partner) {
            $collection = $this->feedbackModel->create()
            ->getCollection()
            ->addFieldToFilter('status', ['neq' => 0])
            ->addFieldToFilter('seller_id', $partner->getSellerId())
            ->setOrder('entity_id', 'DESC')
            ->setPageSize(4)
            ->setCurPage(1);
        }

        return $collection;
    }

    public function getBestsellProducts()
    {
        $products = [];
        $partner = $this->getProfileDetail();
        if ($partner) {
            $catalogProductWebsite = $this->mpProductCollection->create()->getTable('catalog_product_website');
            $helper = $this->mpHelper;
            if (count($helper->getAllWebsites()) == 1) {
                $websiteId = 0;
            } else {
                $websiteId = $helper->getWebsiteId();
            }
            $querydata = $this->mpProductModel->create()
                                ->getCollection()
                                ->addFieldToFilter(
                                    'seller_id',
                                    ['eq' => $partner->getSellerId()]
                                )
                                ->addFieldToFilter(
                                    'status',
                                    ['neq' => 2]
                                )
                                ->addFieldToSelect('mageproduct_id')
                                ->setOrder('mageproduct_id');
            $products = $this->productFactory->create()->getCollection();
            $products->addAttributeToSelect('*');
            $products->addAttributeToFilter('entity_id', ['in' => $querydata->getAllIds()]);
            $products->addAttributeToFilter('visibility', ['in' => [4]]);
            $products->addAttributeToFilter('status', 1);
            if ($websiteId) {
                $products->getSelect()
                ->join(
                    ['cpw' => $catalogProductWebsite],
                    'cpw.product_id = e.entity_id'
                )->where(
                    'cpw.website_id = '.$websiteId
                );
            }
            $products->setPageSize(4)->setCurPage(1)->setOrder('entity_id');
        }

        return $products;
    }

    /**
     * getSellerFlagReasons is used to get the seller Flag Reasons
     * @return \Webkul\Marketplace\Model\ResourceModel\SellerFlagReason\Collection
     */
    public function getSellerFlagReasons()
    {
        $reasonCollection = $this->reasonCollection->create()
                          ->addFieldToFilter('status', self::FLAG_REASON_ENABLE)
                          ->setPageSize(5);
        return $reasonCollection;
    }
    /**
     * getFilterData function
     *
     * @param [string] $content
     * @return string
     */
    public function getFilterData($content)
    {
        if ($content != "") {
            return $this->filterProvider->getPageFilter()->filter($content);
        }
        return $content;
    }
}
