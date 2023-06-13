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

namespace Webkul\Marketplace\Helper;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Webkul\Marketplace\Model\Product as SellerProduct;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Store\Model\ScopeInterface;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory as MpProductCollection;
use Webkul\Marketplace\Model\ResourceModel\Feedback\CollectionFactory as MpFeedbackCollection;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Framework\DB\Sql\Expression as SqlExpression;
use Magento\Framework\DB\Select as DBSelect;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Catalog\Model\Product\Visibility;
use Webkul\Marketplace\Model\ControllersRepository;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
use Magento\Framework\App\ObjectManager;
use Webkul\Marketplace\Model\SaleperpartnerFactory as MpSalesPartner;

/**
 * Webkul Marketplace Helper Data.
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const MARKETPLACE_ADMIN_URL = "admin";

    const URL_TYPE_COLLECTION = "collection";

    const URL_TYPE_FEEDBACK = "feedback";

    const URL_TYPE_LOCATION = "location";

    const URL_TYPE_PROFILE = "profile";

    const MARKETPLACE_ADMIN_NAME = "Admin";

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    protected $_customerSessionFactory;

    /**
     * @var null|array
     */
    protected $_options;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $_product;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @var \Webkul\Marketplace\Model\Seller
     */
    protected $marketplaceSeller;

    /**
     * @var \Magento\Framework\App\Cache\ManagerFactory
     */
    protected $cacheManager;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerModel;

    /**
     * @var Visibility
     */
    protected $visibility;

    /**
     * @var ControllersRepository
     */
    protected $controllersRepository;

    /**
     * @var UrlRewriteFactory
     */
    protected $urlRewriteFactory;

    /**
     * @var \Webkul\Marketplace\Logger\Logger
     */
    protected $logger;

    /**
     * @var MpSalesPartner
     */
    protected $mpSalesPartner;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Customer\Model\SessionFactory $customerSessionFactory
     * @param CollectionFactory $collectionFactory
     * @param HttpContext $httpContext
     * @param \Magento\Catalog\Model\ResourceModel\Product $product
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Directory\Model\Currency $currency
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Framework\App\Cache\ManagerFactory $cacheManagerFactory
     * @param \Magento\Framework\View\Element\BlockFactory $blockFactory
     * @param SellerCollection $sellerCollectionFactory
     * @param ProductCollection $productCollectionFactory
     * @param MpProductCollection $mpProductCollectionFactory
     * @param MpFeedbackCollection $mpFeedbackCollectionFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Webkul\Marketplace\Model\Seller $marketplaceSeller
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Customer\Model\CustomerFactory $customerModel
     * @param Visibility $visibility
     * @param ControllersRepository $controllersRepository
     * @param UrlRewriteFactory $urlRewriteFactory
     * @param MpSalesPartner $mpSalesPartner
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory,
        CollectionFactory $collectionFactory,
        HttpContext $httpContext,
        \Magento\Catalog\Model\ResourceModel\Product $product,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\Cache\ManagerFactory $cacheManagerFactory,
        \Magento\Framework\View\Element\BlockFactory $blockFactory,
        SellerCollection $sellerCollectionFactory,
        ProductCollection $productCollectionFactory,
        MpProductCollection $mpProductCollectionFactory,
        MpFeedbackCollection $mpFeedbackCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory,
        \Webkul\Marketplace\Model\Seller $marketplaceSeller,
        \Magento\Indexer\Model\Indexer\CollectionFactory $indexerCollectionFactory,
        \Magento\Customer\Model\CustomerFactory $customerModel,
        Visibility $visibility,
        ControllersRepository $controllersRepository,
        UrlRewriteFactory $urlRewriteFactory,
        \Webkul\Marketplace\Logger\Logger $logger,
        MpSalesPartner $mpSalesPartner = null,
        \Magento\Framework\Module\Manager $moduleManager = null
    ) {
        $this->_objectManager = $objectManager;
        $this->_customerSessionFactory = $customerSessionFactory;
        $this->_collectionFactory = $collectionFactory;
        $this->httpContext = $httpContext;
        $this->_product = $product;
        parent::__construct($context);
        $this->_currency = $currency;
        $this->_localeCurrency = $localeCurrency;
        $this->_storeManager = $storeManager;
        $this->cacheManager = $cacheManagerFactory;
        $this->_blockFactory = $blockFactory;
        $this->_sellerCollectionFactory = $sellerCollectionFactory;
        $this->_mpProductCollectionFactory = $mpProductCollectionFactory;
        $this->_mpFeedbackCollectionFactory = $mpFeedbackCollectionFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_resource = $resource;
        $this->_localeFormat = $localeFormat;
        $this->indexerFactory = $indexerFactory;
        $this->marketplaceSeller = $marketplaceSeller;
        $this->indexerCollectionFactory = $indexerCollectionFactory;
        $this->customerModel = $customerModel;
        $this->visibility = $visibility;
        $this->controllersRepository = $controllersRepository;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->logger = $logger;
        $this->mpSalesPartner = $mpSalesPartner ?: ObjectManager::getInstance()
                                  ->create(MpSalesPartner::class);
        $this->moduleManager = $moduleManager ?: \Magento\Framework\App\ObjectManager::getInstance()
        ->create(\Magento\Framework\Module\Manager::class);
    }

    /**
     * Return Customer id.
     *
     * @return bool|0|1
     */
    public function getCustomer()
    {
        return $this->_customerSessionFactory->create()->getCustomer();
    }

    /**
     * Return Customer Data
     *
     * @return /Magento/Customer/Model/Customer
     */
    public function getCustomerData()
    {
        $customerId = $this->getCustomerId();
        $customerModel = $this->customerModel->create();
        $customerModel->load($customerId);
        return $customerModel;
    }

    /**
     * Return Customer id.
     *
     * @return bool|0|1
     */
    public function getCustomerId()
    {
        return $this->httpContext->getValue('customer_id');
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }

    /**
     * Return the Customer seller status.
     *
     * @return bool|0|1
     */
    public function isSeller()
    {
        $sellerStatus = 0;
        $sellerId = $this->getCustomerId();
        $model = $this->getSellerCollectionObj($sellerId);
        foreach ($model as $value) {
            if ($value->getIsSeller() == 1) {
                $sellerStatus = $value->getIsSeller();
            }
        }

        return $sellerStatus;
    }

    /**
     * Return the authorize seller status.
     *
     * @return bool|0|1
     */
    public function isRightSeller($productId = '')
    {
        $collection = $this->_mpProductCollectionFactory->create()
            ->addFieldToFilter('mageproduct_id', $productId)
            ->addFieldToFilter('seller_id', $this->getCustomerId());
        if ($collection->getSize()) {
            return true;
        }

        return false;
    }

    /**
     * Return the seller Data.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerData()
    {
        $sellerId = $this->getCustomerId();
        $model = $this->getSellerCollectionObj($sellerId);
        return $model;
    }

    /**
     * Return the seller Product Data.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Product\Collection
     */
    public function getSellerProductData()
    {
        $collection = $this->_mpProductCollectionFactory->create()
            ->addFieldToFilter('seller_id', $this->getCustomerId());

        return $collection;
    }

    /**
     * Return the seller product data by product id.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Product\Collection
     */
    public function getSellerProductDataByProductId($productId = '')
    {
        $collection = $this->_mpProductCollectionFactory->create();
        $collection->addFieldToFilter('mageproduct_id', $productId);
        $collection = $this->joinCustomer($collection);

        return $collection;
    }

    /**
     * Return the seller data by seller id.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerDataBySellerId($sellerId = '')
    {
        $collection = $this->getSellerCollectionObj($sellerId);
        $collection = $this->joinCustomer($collection);

        return $collection;
    }

    /**
     * Return the seller data by seller shop url.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerDataByShopUrl($shopUrl = '')
    {
        $collection = $this->getSellerCollectionObjByShop($shopUrl);
        $collection = $this->joinCustomer($collection);

        return $collection;
    }

    public function getRootCategoryIdByStoreId($storeId = '')
    {
        return $this->_storeManager->getStore($storeId)->getRootCategoryId();
    }

    public function getAllStores()
    {
        return $this->_storeManager->getStores();
    }

    public function getCurrentStoreId()
    {
        // give the current store id
        return $this->_storeManager->getStore()->getStoreId();
    }

    public function getWebsiteId()
    {
        // give the current store id
        return $this->_storeManager->getStore(true)->getWebsite()->getId();
    }

    public function getAllWebsites()
    {
        // give the current store id
        return $this->_storeManager->getWebsites();
    }

    public function getSingleStoreStatus()
    {
        return $this->_storeManager->hasSingleStore();
    }

    public function getSingleStoreModeStatus()
    {
        return $this->_storeManager->isSingleStoreMode();
    }

    public function setCurrentStore($storeId)
    {
        return $this->_storeManager->setCurrentStore($storeId);
    }

    public function getCurrentCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
        // give the currency code
    }

    public function getBaseCurrencyCode()
    {
        return $this->_storeManager->getStore()->getBaseCurrencyCode();
    }

    public function getConfigAllowCurrencies()
    {
        return $this->_currency->getConfigAllowCurrencies();
    }

    /**
     * Retrieve currency rates to other currencies.
     *
     * @param string     $currency
     * @param array|null $toCurrencies
     *
     * @return array
     */
    public function getCurrencyRates($currency, $toCurrencies = null)
    {
        // give the currency rate
        return $this->_currency->getCurrencyRates($currency, $toCurrencies);
    }

    /**
     * Retrieve currency Symbol.
     *
     * @return string
     */
    public function getCurrencySymbol()
    {
        return $this->_localeCurrency->getCurrency(
            $this->getBaseCurrencyCode()
        )->getSymbol();
    }

    /**
     * Retrieve price format.
     *
     * @return string
     */
    public function getPriceFormat()
    {
        return $this->_localeFormat->getPriceFormat('', $this->getBaseCurrencyCode());
    }

    /**
     * @return array|null
     */
    public function getAllowedSets()
    {
        if (null == $this->_options) {
            $this->_options = $this->_collectionFactory->create()
                ->addFieldToFilter(
                    'attribute_set_id',
                    ['in' => explode(',', $this->getAllowedAttributesetIds())]
                )
                ->setEntityTypeFilter($this->_product->getTypeId())
                ->toOptionArray();
        }

        return $this->_options;
    }

    /**
     * Options getter.
     *
     * @return array
     */
    public function getAllowedProductTypes()
    {
        $alloweds = explode(',', $this->getAllowedProductType());
        $data = [
            'simple' => __('Simple'),
            'downloadable' => __('Downloadable'),
            'virtual' => __('Virtual'),
            'configurable' => __('Configurable'),
            'grouped' => __('Grouped Product'),
            'bundle' => __('Bundle Product'),
        ];
        $allowedproducts = [];
        if (isset($alloweds)) {
            foreach ($alloweds as $allowed) {
                if (!empty($data[$allowed])) {
                    array_push(
                        $allowedproducts,
                        ['value' => $allowed, 'label' => $data[$allowed]]
                    );
                }
            }
        }

        return $allowedproducts;
    }

    /**
     * Return the product visibilty options.
     *
     * @return \Magento\Tax\Model\ClassModel
     */
    public function getTaxClassModel()
    {
        return $this->_objectManager->create(\Magento\Tax\Model\ClassModel::class)
            ->getCollection()
            ->addFieldToFilter('class_type', 'PRODUCT');
    }

    /**
     * Return the product visibilty options.
     *
     * @return \Magento\Catalog\Model\Product\Visibility
     */
    public function getVisibilityOptionArray()
    {
        return $this->visibility->getOptionArray();
    }

    /**
     * Return the Seller existing status.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function isSellerExist()
    {
        $sellerId = $this->getCustomerId();
        $model = $this->getSellerCollectionObj($sellerId);
        return $model->getSize();
    }

    /**
     * Return the Seller data by customer Id stored in the session.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSeller()
    {
        $data = [];
        $bannerpic = '';
        $logopic = '';
        $countrylogopic = '';
        $isDefaultBanner = 0;
        $isDefaultLogo = 0;
        $sellerId = $this->getCustomerId();
        $model = $this->getSellerCollectionObj($sellerId);
        $customer = $this->customerModel->create()->load($this->getCustomerId());
        foreach ($model as $value) {
            $data = $value->getData();
            $bannerpic = $value->getBannerPic();
            $logopic = $value->getLogoPic();
            $countrylogopic = $value->getCountryPic();
            if (strlen($bannerpic) <= 0) {
                $bannerpic = $this->getProfileBannerImage();
                $isDefaultBanner = 1;
            }
            if (strlen($logopic) <= 0) {
                $logopic = 'noimage.png';
                $isDefaultLogo = 1;
            }
            if (strlen($countrylogopic) <= 0) {
                $countrylogopic = '';
            }
        }
        $data['banner_pic'] = $bannerpic;
        $data['is_default_banner'] = $isDefaultBanner;
        $data['taxvat'] = $customer->getTaxvat();
        $data['logo_pic'] = $logopic;
        $data['is_default_logo'] = $isDefaultLogo;
        $data['country_pic'] = $countrylogopic;

        return $data;
    }

    /**
     * Return the Seller Model Collection Object.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerCollectionObj($sellerId)
    {
        $collection = $this->getSellerCollection();
        $collection->addFieldToFilter('seller_id', $sellerId);
        $collection->addFieldToFilter('store_id', $this->getCurrentStoreId());
        // If seller data doesn't exist for current store

        if (!$collection->getSize()) {
            $collection = $this->getSellerCollection();
            $collection->addFieldToFilter('seller_id', $sellerId);
            $collection->addFieldToFilter('store_id', 0);
        }

        return $collection;
    }

    /**
     * getSellerStatus return the seller status
     * @param  integer $sellerId [Seller Id]
     * @return string [Seller Status]
     */
    public function getSellerStatus($sellerId = 0)
    {
        $sellerStatus = 0;
        if (!$sellerId) {
            $sellerId = $this->getCustomerId();
        }
        $model = $this->getSellerCollectionObj($sellerId);
        foreach ($model as $value) {
            $sellerStatus = $value->getIsSeller();
        }
        return $sellerStatus;
    }

    /**
     * [getAllowedSellerStatus returns all seller's status array]
     * @return mixed
     */
    public function getAllowedSellerStatus()
    {
        $availableOptions = $this->marketplaceSeller->getAvailableStatuses();
        return $availableOptions;
    }

    /**
     * Return the Seller Model Collection Object.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerCollectionObjByShop($shopUrl)
    {
        $collection = $this->getSellerCollection();
        $collection->addFieldToFilter('is_seller', 1);
        $collection->addFieldToFilter('shop_url', $shopUrl);
        $collection->addFieldToFilter('store_id', $this->getCurrentStoreId());
        // If seller data doesn't exist for current store
        if (!$collection->getSize()) {
            $collection = $this->getSellerCollection();
            $collection->addFieldToFilter('is_seller', 1);
            $collection->addFieldToFilter('shop_url', $shopUrl);
            $collection->addFieldToFilter('store_id', 0);
        }

        return $collection;
    }

    public function getFeedTotal($sellerId)
    {
        $collection = $this->_mpFeedbackCollectionFactory->create();
        $collection->addFieldToFilter('seller_id', $sellerId);
        $collection->addFieldToFilter('status', ['neq' => 0]);
        $collection->addAllRatingColumns();

        foreach ($collection as $item) {
            $result['price'] = $item->getPriceRating();
            $result['value'] = $item->getValueRating();
            $result['quality'] = $item->getQualityRating();
            $result['totalfeed'] = $item->getRating();
            $result['feedcount'] = $item->getCount();
            $result['price_star_5'] = $item->getPriceStar5();
            $result['price_star_4'] = $item->getPriceStar4();
            $result['price_star_3'] = $item->getPriceStar3();
            $result['price_star_2'] = $item->getPriceStar2();
            $result['price_star_1'] = $item->getPriceStar1();
            $result['value_star_5'] = $item->getValueStar5();
            $result['value_star_4'] = $item->getValueStar4();
            $result['value_star_3'] = $item->getValueStar3();
            $result['value_star_2'] = $item->getValueStar2();
            $result['value_star_1'] = $item->getValueStar1();
            $result['quality_star_5'] = $item->getQualityStar5();
            $result['quality_star_4'] = $item->getQualityStar4();
            $result['quality_star_3'] = $item->getQualityStar3();
            $result['quality_star_2'] = $item->getQualityStar2();
            $result['quality_star_1'] = $item->getQualityStar1();
        }

        return $result;
    }

    public function getSelleRating($sellerId)
    {
        $feeds = $this->getFeedTotal($sellerId);
        $totalRating = (
            $feeds['price'] + $feeds['value'] + $feeds['quality']
        ) / 60;

        return round($totalRating, 1, PHP_ROUND_HALF_UP);
    }

    public function getCatatlogGridPerPageValues()
    {
        return $this->scopeConfig->getValue(
            'catalog/frontend/grid_per_page_values',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * [getConfigValue to get the configuration fields value]
     * @param  string $group [Group id]
     * @param  string $field [Field Id]
     * @return string [configuration field selected value]
     */
    public function getConfigValue($group = '', $field = '')
    {
        return  $this->scopeConfig->getValue(
            'marketplace/'.$group.'/'.$field,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getCaptchaEnable()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/captcha',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getDefaultTransEmailId()
    {
        return $this->scopeConfig->getValue(
            'trans_email/ident_general/email',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getAdminEmailId()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/adminemail',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getAllowedCategoryIds()
    {
        $allowedCategories = '';
        $model = $this->getSellerCollection()
        ->addFieldToFilter('seller_id', $this->getCustomerId())
        ->addFieldToFilter('store_id', $this->getCurrentStoreId());
        foreach ($model as $key => $value) {
            $allowedCategories = $value['allowed_categories'];
        }
        if ($allowedCategories == '') {
            $model = $this->getSellerCollection()
            ->addFieldToFilter('seller_id', $this->getCustomerId())
            ->addFieldToFilter('store_id', 0);
            foreach ($model as $key => $value) {
                $allowedCategories = $value['allowed_categories'];
            }
        }
        if ($allowedCategories) {
            return $allowedCategories;
        } else {
            return $this->scopeConfig->getValue(
                'marketplace/product_settings/categoryids',
                ScopeInterface::SCOPE_STORE
            );
        }
    }

    public function getIsProductEditApproval()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/product_edit_approval',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIsPartnerApproval()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/seller_approval',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIsProductApproval()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/product_approval',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getAllowedAttributesetIds()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/attributesetid',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getAllowedProductType()
    {
        $productTypes = $this->scopeConfig->getValue(
            'marketplace/product_settings/allow_for_seller',
            ScopeInterface::SCOPE_STORE
        );
        $data = explode(',', $productTypes);
        foreach ($data as $key => $value) {
            if ($value == 'grouped') {
                if ($this->_moduleManager->isEnabled('Webkul_MpGroupedProduct')) {
                    $data['grouped'] = __('Grouped Product');
                } else {
                    unset($data[$key]);
                }
            }
            if ($value == 'bundle') {
                if ($this->_moduleManager->isEnabled('Webkul_MpBundleProduct')) {
                    $data['bundle'] = __('Bundle Product');
                } else {
                    unset($data[$key]);
                }
            }
        }
        return implode(',', $data);
    }

    public function getUseCommissionRule()
    {
        return $this->scopeConfig->getValue(
            'mpadvancedcommission/options/use_commission_rule',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getCommissionType()
    {
        return $this->scopeConfig->getValue(
            'mpadvancedcommission/options/commission_type',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIsOrderManage()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/order_manage',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getConfigCommissionRate()
    {
        $percent = $this->scopeConfig->getValue(
            'marketplace/general_settings/percent',
            ScopeInterface::SCOPE_STORE
        );
        if ($percent < 0) {
            $percent = 0;
        }
        if ($percent > 100) {
            $percent = 100;
        }
        return $percent;
    }

    public function getConfigTaxManage()
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/tax_manage',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getlowStockNotification()
    {
        return $this->scopeConfig->getValue(
            'marketplace/inventory_settings/low_stock_notification',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getlowStockQty()
    {
        return $this->scopeConfig->getValue(
            'marketplace/inventory_settings/low_stock_amount',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getActiveColorPicker()
    {
        return $this->scopeConfig->getValue(
            'marketplace/profile_settings/activecolorpicker',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getSellerPolicyApproval()
    {
        return $this->scopeConfig->getValue(
            'marketplace/profile_settings/seller_policy_approval',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getUrlRewrite()
    {
        return $this->scopeConfig->getValue(
            'marketplace/profile_settings/url_rewrite',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getReviewStatus()
    {
        return $this->scopeConfig->getValue(
            'marketplace/review_settings/review_status',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplaceHeadLabel()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel1()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel1',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel2()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel2',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel3()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel4()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel4',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getDisplayBanner()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/displaybanner',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerImage()
    {
        return $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/banner/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/banner',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerContent()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/bannercontent',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getDisplayIcon()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/displayicons',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage1()
    {
        $icon = $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon1',
            ScopeInterface::SCOPE_STORE
        );
        if (!$icon) {
            $icon = 'icon-register-yourself.png';
        }
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$icon;
    }

    public function getIconImageLabel1()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon1_label',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage2()
    {
        $icon = $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon2',
            ScopeInterface::SCOPE_STORE
        );
        if (!$icon) {
            $icon = 'icon-add-products.png';
        }
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$icon;
    }

    public function getIconImageLabel2()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon2_label',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage3()
    {
        $icon = $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon3',
            ScopeInterface::SCOPE_STORE
        );
        if (!$icon) {
            $icon = 'icon-start-selling.png';
        }
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$icon;
    }

    public function getIconImageLabel3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon3_label',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage4()
    {
        $icon = $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon4',
            ScopeInterface::SCOPE_STORE
        );
        if (!$icon) {
            $icon = 'icon-collect-revenues.png';
        }
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$icon;
    }

    public function getIconImageLabel4()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon4_label',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacebutton()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacebutton',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplaceprofile()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplaceprofile',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getSellerlisttopLabel()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/sellerlisttop',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getSellerlistbottomLabel()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/sellerlistbottom',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintStatus()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_hint_status',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintCategory()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_category',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintName()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_name',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintDesc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_des',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintShortDesc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_sdes',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintSku()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_sku',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintPrice()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_price',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintSpecialPrice()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_sprice',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintStartDate()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_sdate',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintEndDate()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_edate',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintQty()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_qty',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintStock()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_stock',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintTax()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_tax',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintWeight()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_weight',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintImage()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_image',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductHintEnable()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/producthint_settings/product_enable',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintStatus()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_hint_status',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintBecomeSeller()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/become_seller',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintShopurl()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/shopurl_seller',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintTw()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_tw',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintFb()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_fb',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * [getProfileHintInsta get profile hint of Instagram Id field]
     * @return string
     */
    public function getProfileHintInsta()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_inst',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * [getProfileHintGoogle get profile hint of Google Id field]
     * @deprecated
     *
     * @return string
     */
    public function getProfileHintGoogle()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_google',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * [getProfileHintYoutube get profile hint of youtube Id field]
     * @return string
     */
    public function getProfileHintYoutube()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_youtube',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * [getProfileHintVimeo get profile hint of Vimeo Id field]
     * @return string
     */
    public function getProfileHintVimeo()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_vimeo',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * [getProfileHintPinterest get profile hint of Pinterest Id field]
     * @return string
     */
    public function getProfileHintPinterest()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_pin',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintCn()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_cn',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * [getProfileHintTax get profile hint of Tax/VAT Number field]
     * @return string
     */
    public function getProfileHintTax()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_tax',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintBc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_bc',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintShop()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_shop',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintBanner()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_banner',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintLogo()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_logo',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintLoc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_loc',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintDesc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_desciption',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintReturnPolicy()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/returnpolicy',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintShippingPolicy()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/shippingpolicy',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintCountry()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_country',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintMeta()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_meta',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintMetaDesc()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_mdesc',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileHintBank()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/profilehint_settings/profile_bank',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProfileUrl()
    {
        $targetUrl = $this->getTargetUrlPath();
        if ($targetUrl) {
            $temp = explode('/profile/shop', $targetUrl);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            $temp = explode('/', $temp[1]);
            if (isset($temp[1]) && $temp[1] != '') {
                $temp1 = explode('?', $temp[1]);

                return $temp1[0];
            }
        }

        return false;
    }

    public function getCollectionUrl()
    {
        $targetUrl = $this->getTargetUrlPath();
        if ($targetUrl) {
            $temp = explode('/collection/shop', $targetUrl);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            $temp = explode('/', $temp[1]);
            if (isset($temp[1]) && $temp[1] != '') {
                $temp1 = explode('?', $temp[1]);

                return $temp1[0];
            }
        }

        return false;
    }

    public function getLocationUrl()
    {
        $targetUrl = $this->getTargetUrlPath();
        if ($targetUrl) {
            $temp = explode('/location/shop', $targetUrl);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            $temp = explode('/', $temp[1]);
            if (isset($temp[1]) && $temp[1] != '') {
                $temp1 = explode('?', $temp[1]);

                return $temp1[0];
            }
        }

        return false;
    }

    public function getFeedbackUrl()
    {
        $targetUrl = $this->getTargetUrlPath();
        if ($targetUrl) {
            $temp = explode('/feedback/shop', $targetUrl);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            $temp = explode('/', $temp[1]);
            if (isset($temp[1]) && $temp[1] != '') {
                $temp1 = explode('?', $temp[1]);

                return $temp1[0];
            }
        }

        return false;
    }

    public function getRewriteUrl($targetUrl)
    {
        $requestUrl = $this->_urlBuilder->getUrl(
            '',
            [
                '_direct' => $targetUrl,
                '_secure' => $this->_request->isSecure(),
            ]
        );
        $urlColl = $this->getUrlRewriteCollection()
            ->addFieldToFilter('target_path', $targetUrl)
            ->addFieldToFilter('store_id', $this->getCurrentStoreId());
        foreach ($urlColl as $value) {
            $requestUrl = $this->_urlBuilder->getUrl(
                '',
                [
                    '_direct' => $value->getRequestPath(),
                    '_secure' => $this->_request->isSecure(),
                ]
            );
        }

        return $requestUrl;
    }

    public function getRewriteUrlPath($targetUrl)
    {
        $requestPath = '';
        $urlColl = $this->getUrlRewriteCollection()
            ->addFieldToFilter(
                'target_path',
                $targetUrl
            )
            ->addFieldToFilter(
                'store_id',
                $this->getCurrentStoreId()
            );
        foreach ($urlColl as $value) {
            $requestPath = $value->getRequestPath();
        }

        return $requestPath;
    }

    public function getTargetUrlPath()
    {
        try {
            $urls = explode(
                $this->_urlBuilder->getUrl(
                    '',
                    ['_secure' => $this->_request->isSecure()]
                ),
                $this->_urlBuilder->getCurrentUrl()
            );
            $targetUrl = '';
            if (empty($urls[1])) {
                $urls[1] = '';
            }
            $temp = explode('/?', $urls[1]);
            if (!isset($temp[1])) {
                $temp[1] = '';
            }
            if (!$temp[1]) {
                $temp = explode('?', $temp[0]);
            }
            $requestPath = $temp[0];
            $urlColl = $this->getUrlRewriteCollection()
                ->addFieldToFilter(
                    'request_path',
                    ['eq' => $requestPath]
                )
                ->addFieldToFilter(
                    'store_id',
                    ['eq' => $this->getCurrentStoreId()]
                );
            foreach ($urlColl as $value) {
                $targetUrl = $value->getTargetPath();
            }
        } catch (\Exception $e) {
            $this->logDataInLogger("Helper_Data getTargetUrlPath : ".$e->getMessage());
            $targetUrl = '';
        }

        return $targetUrl;
    }

    public function getPlaceholderImage()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/placeholder/image.jpg';
    }

    public function getSellerProCount($sellerId)
    {
        $querydata = $this->_mpProductCollectionFactory->create()
            ->addFieldToFilter('seller_id', $sellerId)
            ->addFieldToFilter('status', ['neq' => SellerProduct::STATUS_DISABLED])
            ->addFieldToSelect('mageproduct_id')
            ->setOrder('mageproduct_id');
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('entity_id', ['in' => $querydata->getData()]);
        $collection->addAttributeToFilter('visibility', ['in' => [2,4]]);
        $collection->addAttributeToFilter('status', ['neq' => SellerProduct::STATUS_DISABLED]);
        $collection->addStoreFilter();
        return $collection->getSize();
    }

    public function getMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        );
    }

    public function getMaxDownloads()
    {
        return $this->scopeConfig->getValue(
            \Magento\Downloadable\Model\Link::XML_PATH_DEFAULT_DOWNLOADS_NUMBER,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getConfigPriceWebsiteScope()
    {
        $scope = $this->scopeConfig->getValue(
            \Magento\Store\Model\Store::XML_PATH_PRICE_SCOPE,
            ScopeInterface::SCOPE_STORE
        );
        if ($scope == \Magento\Store\Model\Store::PRICE_SCOPE_WEBSITE) {
            return true;
        }

        return false;
    }

    public function getSkuType()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/sku_type',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getSkuPrefix()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/sku_prefix',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getSellerProfileDisplayFlag()
    {
        return $this->scopeConfig->getValue(
            'marketplace/profile_settings/seller_profile_display',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getAutomaticUrlRewrite()
    {
        return $this->scopeConfig->getValue(
            'marketplace/profile_settings/auto_url_rewrite',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve YouTube API key
     *
     * @return string
     */
    public function getYouTubeApiKey()
    {
        return $this->scopeConfig->getValue(
            'catalog/product_video/youtube_api_key'
        );
    }

    public function getAllowedControllersBySetData($allowedModule)
    {
        $allowedModuleArr=[];
        if ($allowedModule && $allowedModule!='all') {
            $allowedModuleControllers = explode(',', $allowedModule);
            foreach ($allowedModuleControllers as $key => $value) {
                array_push($allowedModuleArr, $value);
            }
        } else {
            $controllersRepository = $this->controllersRepository;
            $controllersList = $controllersRepository->getList();
            foreach ($controllersList as $key => $value) {
                array_push($allowedModuleArr, $value['controller_path']);
            }
        }
        return $allowedModuleArr;
    }

    public function isSellerGroupModuleInstalled()
    {
        if ($this->_moduleManager->isEnabled('Webkul_MpSellerGroup')) {
            return true;
        }
        return false;
    }

    public function isAllowedAction($actionName = '')
    {
        if ($this->isSellerGroupModuleInstalled()) {
            $sellerGroupHelper = $this->_objectManager->create(
                \Webkul\MpSellerGroup\Helper\Data::class
            );
            if (!$sellerGroupHelper->getStatus()) {
                return true;
            }
            $sellerId = $this->getCustomerId();
            $sellerGroupTypeRepository = $this->_objectManager->create(
                \Webkul\MpSellerGroup\Api\SellerGroupTypeRepositoryInterface::class
            );
            if (!$sellerGroupTypeRepository->getBySellerCount($sellerId)) {
                $products = $this->_mpProductCollectionFactory->create()
                ->addFieldToFilter(
                    'seller_id',
                    $this->getCustomerId()
                );
                $getDefaultGroupStatus = $sellerGroupHelper->getDefaultGroupStatus();
                if ($getDefaultGroupStatus) {
                    $allowqty = $sellerGroupHelper->getDefaultProductAllowed();
                    $allowFunctionalities = explode(',', $sellerGroupHelper->getDefaultAllowedFeatures());
                    if ($allowqty >= count($products)) {
                        if (in_array($actionName, $allowFunctionalities, true)) {
                            return true;
                        }
                    }
                }
            }
            $getSellerGroup = $sellerGroupTypeRepository->getBySellerId($sellerId);
            if (count($getSellerGroup->getData())) {
                $getSellerTypeGroup = $getSellerGroup;
                $allowedModuleArr = $this->getAllowedControllersBySetData(
                    $getSellerTypeGroup['allowed_modules_functionalities']
                );
                if (in_array($actionName, $allowedModuleArr, true)) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    public function getPageLayout()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/pageLayout',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getDisplayBannerLayout2()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/displaybannerLayout2',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerImageLayout2()
    {
        return $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/banner/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/bannerLayout2',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerContentLayout2()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/bannercontentLayout2',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerButtonLayout2()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacebuttonLayout2',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getTermsConditionUrlLayout2()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/termConditionLinkLayout2',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getDisplayBannerLayout3()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/displaybannerLayout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerImageLayout3()
    {
        return $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/banner/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/bannerLayout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerContentLayout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/bannercontentLayout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getBannerButtonLayout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacebuttonLayout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getTermsConditionUrlLayout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/termConditionLinkLayout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getDisplayIconLayout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/displayiconsLayout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage1Layout3()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon1_layout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel1Layout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon1_label_layout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage2Layout3()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon2_layout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel2Layout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon2_label_layout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage3Layout3()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon3_layout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel3Layout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon3_label_layout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage4Layout3()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon4_layout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel4Layout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon4_label_layout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImage5Layout3()
    {
        return  $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ).'marketplace/icon/'.$this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon5_layout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIconImageLabel5Layout3()
    {
        return  $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/feature_icon5_label_layout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel1Layout3()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel1Layout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel2Layout3()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel2Layout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getMarketplacelabel3Layout3()
    {
        return $this->scopeConfig->getValue(
            'marketplace/landingpage_settings/marketplacelabel3Layout3',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getOrderApprovalRequired()
    {
        return $this->scopeConfig->getValue(
            'marketplace/order_settings/order_approval',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getAllowProductLimit()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/allow_product_limit',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getGlobalProductLimitQty()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/global_product_limit',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getOrderedPricebyorder($order, $price)
    {
        /*
        * Get Current Store Currency Rate
        */
        $currentCurrencyCode = $order->getOrderCurrencyCode();
        $baseCurrencyCode = $order->getBaseCurrencyCode();
        $allowedCurrencies = $this->getConfigAllowCurrencies();
        $rates = $this->getCurrencyRates(
            $baseCurrencyCode,
            array_values($allowedCurrencies)
        );
        if (empty($rates[$currentCurrencyCode])) {
            $rates[$currentCurrencyCode] = 1;
        }
        return $price / $rates[$currentCurrencyCode];
    }

    public function isSellerCouponModuleInstalled()
    {
        if ($this->_moduleManager->isEnabled('Webkul_MpSellerCoupons')) {
            return true;
        }
        return false;
    }

    public function getCustomerSharePerWebsite()
    {
        return $this->scopeConfig->getValue(
            'customer/account_share/scope',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isMpcashondeliveryModuleInstalled()
    {
        if ($this->_moduleManager->isEnabled('Webkul_Mpcashondelivery')) {
            return true;
        }

        return false;
    }

    public function getFormatedPrice($price = 0)
    {
        $currency = $this->_localeCurrency->getCurrency(
            $this->getBaseCurrencyCode()
        );
        return $currency->toCurrency(sprintf("%f", $price));
    }

    public function getIsSeparatePanel()
    {
        return $this->scopeConfig->getValue(
            'marketplace/layout_settings/is_separate_panel',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIsAdminViewCategoryTree()
    {
        return $this->scopeConfig->getValue(
            'marketplace/product_settings/is_admin_view_category_tree',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Prepare Permissions Mapping with controllers.
     *
     * @return array
     */
    public function getControllerMappedPermissions()
    {
        return [
            'marketplace/account/askquestion' => 'marketplace/account/dashboard',
            'marketplace/account_dashboard/tunnel' => 'marketplace/account/dashboard',
            'marketplace/account/chart' => 'marketplace/account/dashboard',
            'marketplace/account/becomesellerPost' => 'marketplace/account/becomeseller',
            'marketplace/account/deleteSellerBanner' => 'marketplace/account/editProfile',
            'marketplace/account/deleteSellerLogo' => 'marketplace/account/editProfile',
            'marketplace/account/editProfilePost' => 'marketplace/account/editProfile',
            'marketplace/account/rewriteUrlPost' => 'marketplace/account/editProfile',
            'marketplace/account/savePaymentInfo' => 'marketplace/account/editProfile'
        ];
    }

    public function isMpSellerProductSearchModuleInstalled()
    {
        if ($this->_moduleManager->isEnabled('Webkul_MpSellerProductSearch')) {
            return true;
        }
        return false;
    }

    public function getImageSize($image)
    {
        try {
            list($width, $height) = getimagesize($image);
            return ['width'=>$width, 'height'=>$height];
        } catch (\Exception $e) {
            $this->logDataInLogger("Helper_Data getImageSize : ".$e->getMessage());
            return [];
        }
    }

    public function isSellerSliderModuleInstalled()
    {
        if ($this->_moduleManager->isEnabled('Webkul_Mpsellerslider')) {
            return true;
        }

        return false;
    }

    public function validateXssString($value = null)
    {
        $notAllowedEvents = [
            // Mouse Event Attributes
            'onclick',
            'ondblclick',
            'onmousedown',
            'onmousemove',
            'onmouseout',
            'onmouseover',
            'onmouseup',
            'onmousewheel',
            'onwheel',
            // Window Event Attributes
            'onafterprint',
            'onbeforeprint',
            'onbeforeunload',
            'onerror',
            'onhashchange',
            'onload',
            'onmessage',
            'onoffline',
            'ononline',
            'onpagehide',
            'onpageshow',
            'onpopstate',
            'onresize',
            'onstorage',
            'onunload',
            // Form Events
            'onblur',
            'onchange',
            'oncontextmenu',
            'onfocus',
            'oninput',
            'oninvalid',
            'onreset',
            'onsearch',
            'onselect',
            'onsubmit',
            // Keyboard Events
            'onkeydown',
            'onkeypress',
            'onkeyup',
            // Drag Events
            'ondrag',
            'ondragend',
            'ondragenter',
            'ondragleave',
            'ondragover',
            'ondragstart',
            'ondrop',
            'onscroll',
            // Clipboard Events
            'oncopy',
            'oncut',
            'onpaste',
            // Media Events
            'onabort',
            'oncanplay',
            'oncanplaythrough',
            'oncuechange',
            'ondurationchange',
            'onemptied',
            'onended',
            'onloadeddata',
            'onloadedmetadata',
            'onloadstart',
            'onpause',
            'onplay',
            'onplaying',
            'onprogress',
            'onratechange',
            'onseeked',
            'onseeking',
            'onstalled',
            'onsuspend',
            'ontimeupdate',
            'onvolumechange',
            'onwaiting',
            // Misc Events
            'onshow',
            'ontoggle',
        ];
        foreach ($notAllowedEvents as $event) {
            $value = preg_replace("/".$event."=.*?/s", "", $value) ? : $value;
        }
        return $value;
    }

    /**
     * Retrieve logo image URL
     *
     * @return string
     */
    public function getSellerDashboardLogoUrl()
    {
        $storeLogoPath = $this->scopeConfig->getValue(
            'marketplace/layout_settings/logo',
            ScopeInterface::SCOPE_STORE
        );
        $url = '';
        if ($storeLogoPath) {
            $url = $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            ).'marketplace/logo/'.$storeLogoPath;
        }
        return $url;
    }

    /**
     * Clean Cache
     */
    public function clearCache()
    {
        $cacheManager = $this->cacheManager->create();
        $availableTypes = $cacheManager->getAvailableTypes();
        $cacheManager->clean($availableTypes);
    }

    /**
     * Get Weight Unit
     *
     * @return string
     */
    public function getWeightUnit()
    {
        return $this->scopeConfig->getValue(
            'general/locale/weight_unit',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getCurrentCurrencyPrice($currencyRate, $basePrice)
    {
        if (!$currencyRate) {
            $currencyRate = 1;
        }
        return $basePrice * $currencyRate;
    }

    /**
     * Get Seller registration url
     *
     * @return string
     */
    public function getSellerRegistrationUrl()
    {
        $url = $this->_urlBuilder->getUrl(
            'customer/account/create',
            [
                '_secure' => $this->_request->isSecure(),
            ]
        );
        if ($this->moduleManager->isOutputEnabled('Webkul_MpVendorRegistration') && $this->scopeConfig->getValue(
            'vendor_registration_section/vendor_registration/visible_registration',
            ScopeInterface::SCOPE_STORE
        )) {
            $url = $this->_urlBuilder->getUrl(
                'vendorregistration/seller/register',
                [
                    '_secure' => $this->_request->isSecure(),
                ]
            );
        }

        return $url;
    }

    /**
     * Check whether seller profile is complete or not
     *
     * @return boolean
     */
    public function isProfileCompleted()
    {
        $sellerData = $this->getSeller();
        $fields = [
            "twitter_id",
            "facebook_id",
            "banner_pic",
            "logo_pic",
            "company_locality",
            "country_pic",
            "company_description"
        ];

        try {
            foreach ($fields as $field) {
                if ($sellerData[$field] == "") {
                    return false;
                }
            }
        } catch (\Exception $e) {
            $this->logDataInLogger("Helper_Data isProfileCompleted : ".$e->getMessage());
            return false;
        }

        return true;
    }

    public function getRequestVar()
    {
        return "seller";
    }

    public function isSellerFilterActive()
    {
        $filter = trim($this->_request->getParam($this->getRequestVar()));
        if ($filter != "") {
            return true;
        }

        return false;
    }

    /**
     * Return the seller data by seller id.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerInfo($sellerId)
    {
        $sellerId = (int) $sellerId;
        $details = ['shop_url' => '', 'shop_title' => '', 'product_count' => ''];
        $sellerCollection = $this->getSellerCollectionObj($sellerId);
        $sellerCollection = $this->joinCustomer($sellerCollection);

        $collection = $this->_productCollectionFactory->create();
        $collection->joinSellerProducts();
        $collection->resetColumns();
        $collection->addFieldToCollection("count", "count(mp_product.seller_id)");
        $collection->getSelect()->where("mp_product.seller_id = $sellerId");
        $query = $collection->getSelectSql(true);
        $sellerCollection->resetColumns();
        $fields = ["entity_id", "shop_title", "shop_url", "company_locality", "logo_pic"];
        $sellerCollection->addFieldsToCollection($fields);
        $sellerCollection->addFieldToCollection("product_count", "($query)");
        $data = $sellerCollection->getData();

        foreach ($data as $key => $info) {
            return $info;
        }

        return $details;
    }

    /**
     * Return the seller data by seller id.
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerProductCollection($sellerId, $productId = 0, $productCount = 0)
    {
        $sellerId = (int) $sellerId;
        $collection = $this->_productCollectionFactory->create();
        $collection->joinSellerProducts();

        if ($productCount > 5 && $productId > 0) {
            $collection->getSelect()->where("mp_product.seller_id = $sellerId and e.entity_id != $productId");
        } else {
            $collection->getSelect()->where("mp_product.seller_id = $sellerId");
        }

        $collection->getSelect()->limit(5);
        return $collection;
    }

    /**
     * Get card type to display on frontend
     *
     * @return int
     */
    public function getDisplayCardType()
    {
        $path = 'marketplace/profile_settings/card_type';
        $scope = ScopeInterface::SCOPE_STORE;
        $cardType = (int) $this->scopeConfig->getValue($path, $scope);
        if ($cardType == 0) {
            $cardType = 1;
        }

        return $cardType;
    }

    /**
     * Get image url of product
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $imageType
     *
     * @return string
     */
    public function getImageUrl($product, $imageType = 'product_page_image_small')
    {
        $imageUrl = "";
        try {
            $imageBlock = $this->_blockFactory->createBlock(
                \Magento\Catalog\Block\Product\ListProduct::class
            );
            $productImage = $imageBlock->getImage($product, $imageType);
            $imageUrl = $productImage->getImageUrl();
        } catch (\Exception $e) {
            $this->logDataInLogger("Helper_Data getImageUrl : ".$e->getMessage());
            $imageUrl = "";
        }

        return $imageUrl;
    }

    /**
     * Check whether seller filter in layered navigation is allowed or not
     *
     * @return bool
     */
    public function allowSellerFilter()
    {
        return $this->scopeConfig->getValue('marketplace/layered_navigation/enable', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get admin discplay name in layered navigation
     *
     * @return string
     */
    public function getAdminFilterDisplayName()
    {
        return $this->scopeConfig->getValue('marketplace/layered_navigation/admin_name', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check whether not visible individually product is allowed in cart
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return bool
     */
    public function allowProductInCart($product)
    {
        return false;
    }

    /**
     * Get Seller Profile Details
     *
     * @param string $type
     *
     * @return \Webkul\Marketplace\Model\Seller | bool
     */
    public function getProfileDetail($type = self::URL_TYPE_PROFILE)
    {
        if ($type == self::URL_TYPE_COLLECTION) {
            $shopUrl = $this->getCollectionUrl();
        } elseif ($type == self::URL_TYPE_FEEDBACK) {
            $shopUrl = $this->getFeedbackUrl();
        } elseif ($type == self::URL_TYPE_LOCATION) {
            $shopUrl = $this->getLocationUrl();
        } elseif ($type == self::URL_TYPE_PROFILE) {
            $shopUrl = $this->getProfileUrl();
        } else {
            $shopUrl = "";
        }

        if (!$shopUrl) {
            $shopUrl = $this->_request->getParam('shop');
        }

        if ($shopUrl) {
            $data = $this->getSellerCollectionObjByShop($shopUrl);
            foreach ($data as $seller) {
                return $seller;
            }
        }

        return false;
    }

    /**
     * Get rewrite url collection
     *
     * @return \Magento\UrlRewrite\Model\ResourceModel\UrlRewrite\Collection
     */
    public function getUrlRewriteCollection()
    {
        return $this->urlRewriteFactory->create()->getCollection();
    }

    /**
     * Get param
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getParam($key)
    {
        return $this->_request->getParam($key);
    }

    /**
     * Check whether Wysiwyg is enabled or not
     *
     * @return bool
     */
    public function isWysiwygEnabled()
    {
        $wysiwygState = $this->scopeConfig->getValue(
            Config::WYSIWYG_STATUS_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE,
            $this->getCurrentStoreId()
        );

        if ($wysiwygState == Config::WYSIWYG_DISABLED) {
            return false;
        }

        return true;
    }

    /**
     * Get Admin Name for Email
     *
     * @return string
     */
    public function getAdminName()
    {
        $path = 'marketplace/general_settings/admin_name';
        $scope = ScopeInterface::SCOPE_STORE;
        $name = trim($this->scopeConfig->getValue($path, $scope));
        if ($name != "") {
            return $name;
        }

        return self::MARKETPLACE_ADMIN_NAME;
    }

    /**
     * Join with Customer Collection
     *
     * @param object $collection
     *
     * @return object
     */
    public function joinCustomer($collection)
    {
        try {
            $collection->joinCustomer();
            if ($this->getCustomerSharePerWebsite()) {
                $websiteId = $this->getWebsiteId();
                $collection->addFieldToFilter('website_id', $websiteId);
            }
        } catch (\Exception $e) {
            $this->logDataInLogger("Helper_Data joinCustomer : ".$e->getMessage());
            return $collection;
        }

        return $collection;
    }

    /**
     * Get Seller Collection
     *
     * @return \Webkul\Marketplace\Model\ResourceModel\Seller\Collection
     */
    public function getSellerCollection()
    {
        return $this->_sellerCollectionFactory->create();
    }

    /**
     * Check Seller's Url are Included or not in Sitemap
     *
     * @return bool
     */
    public function includeSellerUrlInSitemap()
    {
        return $this->scopeConfig->getValue(
            'marketplace/sitemap/enable',
            ScopeInterface::SCOPE_STORE,
            $this->getCurrentStoreId()
        );
    }

    /**
     * Check Profile Url are Included or not in Sitemap
     *
     * @return bool
     */
    public function includeProfileUrlInSitemap()
    {
        return $this->scopeConfig->getValue(
            'marketplace/sitemap/allow_profile_url',
            ScopeInterface::SCOPE_STORE,
            $this->getCurrentStoreId()
        );
    }

    /**
     * Get Frequency of Profile Url in Sitemap
     *
     * @return bool
     */
    public function getFrequencyOfProfileUrlInSitemap()
    {
        $frequency = $this->scopeConfig->getValue(
            'marketplace/sitemap/profile_url_changefreq',
            ScopeInterface::SCOPE_STORE,
            $this->getCurrentStoreId()
        );

        if (empty($frequency)) {
            $frequency = "daily";
        }

        return $frequency;
    }

    /**
     * Get Priority of Profile Url in Sitemap
     *
     * @return bool
     */
    public function getPriorityOfProfileUrlInSitemap()
    {
        $priority = $this->scopeConfig->getValue(
            'marketplace/sitemap/profile_url_priority',
            ScopeInterface::SCOPE_STORE,
            $this->getCurrentStoreId()
        );

        if ($priority == "") {
            $priority = "0.5";
        }

        return $priority;
    }

    /**
     * Check Collection Url are Included or not in Sitemap
     *
     * @return bool
     */
    public function includeCollectionUrlInSitemap()
    {
        return $this->scopeConfig->getValue(
            'marketplace/sitemap/allow_collection_url',
            ScopeInterface::SCOPE_STORE,
            $this->getCurrentStoreId()
        );
    }

    /**
     * Get Frequency of Collection Url in Sitemap
     *
     * @return bool
     */
    public function getFrequencyOfCollectionUrlInSitemap()
    {
        $frequency = $this->scopeConfig->getValue(
            'marketplace/sitemap/collection_url_changefreq',
            ScopeInterface::SCOPE_STORE,
            $this->getCurrentStoreId()
        );

        if (empty($frequency)) {
            $frequency = "daily";
        }

        return $frequency;
    }

    /**
     * Get Priority of Collection Url in Sitemap
     *
     * @return bool
     */
    public function getPriorityOfCollectionUrlInSitemap()
    {
        $priority = $this->scopeConfig->getValue(
            'marketplace/sitemap/collection_url_priority',
            ScopeInterface::SCOPE_STORE,
            $this->getCurrentStoreId()
        );

        if ($priority == "") {
            $priority = "0.5";
        }

        return $priority;
    }

    /**
     * Check Action is Allowed or Not
     *
     * @param string|array $action
     *
     * @return boolean
     */
    public function isAllowed($action)
    {
        $error = "";
        try {
            if (!$this->isSellerGroupModuleInstalled()) {
                return true;
            }

            if (is_array($action)) {
                foreach ($action as $childAction) {
                    if ($this->isAllowedAction($childAction)) {
                        return true;
                    }
                }

                return false;
            }

            if ($this->isAllowedAction($action)) {
                return true;
            }
        } catch (\Exception $e) {
            $this->logDataInLogger("Helper_Data isAllowed : ".$e->getMessage());
        }

        return false;
    }

    /**
     * Check if Flat Product is enabled
     *
     * @return boolean
     */
    public function isFlatProductEnable()
    {
        return $this->scopeConfig->getValue(
            'catalog/frontend/flat_catalog_product',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if Flat Category is enabled
     *
     * @return boolean
     */
    public function isFlatCategoryEnable()
    {
        return $this->scopeConfig->getValue(
            'catalog/frontend/flat_catalog_category',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Re-index Data
     */
    public function reIndexData()
    {
        if ($this->isFlatProductEnable() || $this->isFlatCategoryEnable()) {
            $error = false;
            $index = "catalog_product_flat";
            try {
                $idx = $this->indexerFactory->create()->load($index);
                $idx->reindexAll($index);
            } catch (\Exception $e) {
                $this->logDataInLogger("Helper_Data reIndexData : ".$e->getMessage());
                $error = true;
            }
        }
    }
    /**
     * [getSellerFlagData get seller flag fields data]
     * @param  string $field
     * @return bool|string
     */
    public function getSellerFlagData($field)
    {
          return $this->scopeConfig->getValue(
              'marketplace/seller_flag/'.$field,
              ScopeInterface::SCOPE_STORE
          );
    }
    /**
     * to check seller flag status
     * @return bool
     */
    public function getSellerFlagStatus()
    {
        if ($this->getSellerFlagData('status') &&
        ($this->getSellerFlagData('guest_status') || $this->isCustomerLoggedIn())
        ) {
            return true;
        }
        return false;
    }
    /**
     * [getProductFlagData get product flag fields data]
     * @param  string $field
     * @return bool|string
     */
    public function getProductFlagData($field)
    {
         return $this->scopeConfig->getValue(
             'marketplace/product_flag/'.$field,
             ScopeInterface::SCOPE_STORE
         );
    }
    /**
     * to check product flag status
     * @return bool
     */
    public function getProductFlagStatus()
    {
        if ($this->getProductFlagData('status') &&
        ($this->getProductFlagData('guest_status') || $this->isCustomerLoggedIn())
        ) {
            return true;
        }
        return false;
    }
    /**
     * [logDataInLogger  is used to log the data in the marktplace.log file]
     * @param  string $data
     * @return
     */
    public function logDataInLogger($data)
    {
        $this->logger->info($data);
    }

    /**
     * Return the seller Id by product id.
     *
     * @return int||null
     */
    public function getSellerIdByProductId($productId = '')
    {
        $collection = $this->_mpProductCollectionFactory->create();
        $collection->addFieldToFilter('mageproduct_id', $productId);
        $sellerId = $collection->getFirstItem()->getSellerId();
        return $sellerId;
    }
    /**
     * getSellerList is used to get the list of all the sellers
     * @return mixed
     */
    public function getSellerList()
    {
        $sellerCollection = $this->getSellerCollection();
        $sellerList[] = ['value' => '', 'label' => __('Please Select')];
        $customerGridFlat = $this->getSellerCollection()->getTable('customer_grid_flat');
        $sellerCollection->getSelect()->join(
            $customerGridFlat.' as cgf',
            'main_table.seller_id = cgf.entity_id',
            [
                'name'=>'name',
            ]
        )->where('main_table.store_id = 0 AND main_table.is_seller = 1');
        foreach ($sellerCollection as $item) {
            $sellerList[] = ['value' => $item->getSellerId(), 'label' => $item->getName()];
        }
        return $sellerList;
    }

    /**
     * getParams function used to get the http params
     *
     * @return mixed[]
     */
    public function getParams()
    {
        $paramsData = $this->_customerSessionFactory->create()->getEarningParams();
        return json_decode($paramsData, true);
    }
    /**
     * getMinOrderSettings function
     *
     * @return bool
     */
    public function getMinOrderSettings() : bool
    {
        return $this->scopeConfig->getValue(
            'marketplace/min_order_settings/min_order_status',
            ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * getAnalyticStatus function
     *
     * @return bool
     */
    public function getAnalyticStatus() : bool
    {
        return $this->scopeConfig->getValue(
            'marketplace/general_settings/google_analytic',
            ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * getMinOrderAmount function
     *
     * @return float
     */
    public function getMinOrderAmount() : float
    {
        return $this->scopeConfig->getValue(
            'marketplace/min_order_settings/amount',
            ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * getMinAmountForSeller function
     *
     * @return bool
     */
    public function getMinAmountForSeller() : bool
    {
        return $this->scopeConfig->getValue(
            'marketplace/min_order_settings/for_seller',
            ScopeInterface::SCOPE_STORE
        );
    }
    /**
     * getSellerItemsDetails function
     *
     * @param \Magento\Quote\Model\Quote\Item $items
     * @return mixed[]
     */
    public function getSellerItemsDetails($items)
    {
        $sellerDetails = [];
        foreach ($items as $item) {
            $sellerId = 0;
            $mpassignproductId = $this->getAssignProduct($item);
            $sellerId = $this->getSellerId($mpassignproductId, $item->getProductId());
            $itemPrice = $item->getBaseRowTotal();
            if (!isset($sellerDetails[$sellerId])) {
                $sellerDetails[$sellerId] = $itemPrice;
            } else {
                $sellerDetails[$sellerId] = $sellerDetails[$sellerId] + $itemPrice;
            }
        }
        return $sellerDetails;
    }
    
    /**
     * get assign product id.
     *
     * @param object $item
     *
     * @return int
     */
    protected function getAssignProduct($item)
    {
        $infoBuyRequest = $item->getProductOptionByCode('info_buyRequest');
        $mpAssignProductId = 0;
        if (isset($infoBuyRequest['mpassignproduct_id'])) {
            $mpAssignProductId = $infoBuyRequest['mpassignproduct_id'];
        }

        return $mpAssignProductId;
    }
    /**
     * get seller id.
     *
     * @param int $mpassignproductId
     * @param int $proid
     *
     * @return int
     */
    public function getSellerId($mpassignproductId, $proid)
    {
        $sellerId = 0;
        if ($mpassignproductId) {
            // phpcs:disable
            $this->assignItemsFactory = ObjectManager::getInstance()->create(
                \Webkul\MpAssignProduct\Model\ItemsFactory::class
            );
            // phpcs:enable
            $mpassignModel = $this->assignItemsFactory->create()->load($mpassignproductId);
            $sellerId = $mpassignModel->getSellerId();
        } else {
            $collection = $this->_mpProductCollectionFactory->create()
                                ->addFieldToFilter('mageproduct_id', ['eq' => $proid]);
            foreach ($collection as $temp) {
                $sellerId = $temp->getSellerId();
            }
        }

        return $sellerId;
    }
    /**
     * getSellerProducts function
     *
     * @param [int] $sellerId
     * @return mixed[]
     */
    public function getSellerProducts($sellerId)
    {
        $productCollection = $this->_mpProductCollectionFactory->create()
                                    ->addFieldToFilter('seller_id', ['eq' => $sellerId])
                                    ->addFieldToFilter('status', ['eq' => 1]);
        return $productCollection->getAllIds();
    }
    /**
     * getSellerAnalyticId function
     *
     * @param integer $sellerId
     * @return string
     */
    public function getSellerAnalyticId($sellerId = 0)
    {
        $analyticId = '';
        $salePerPartnerModel = $this->mpSalesPartner->create()
                                    ->getCollection()
                                    ->addFieldToFilter('seller_id', $sellerId)
                                    ->addFieldToFilter('analytic_id', ['neq' => null]);
        if ($salePerPartnerModel->getSize()) {
            $analyticId = $salePerPartnerModel->getFirstItem()
                                                ->getAnalyticId();
        }
        return $analyticId;
    }
    /**
     * getProfileBannerImage function
     *
     * @return string
     */
    public function getProfileBannerImage()
    {
        $banner = $this->scopeConfig->getValue(
            'marketplace/profile_settings/banner',
            ScopeInterface::SCOPE_STORE
        );
        if ($banner === null) {
            $banner = 'banner-image.png';
        }
        return $banner;
    }
}
