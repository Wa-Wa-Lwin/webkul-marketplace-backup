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

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Model\ProductFactory as MpProductModel;

/**
 * Seller Product's Collection Block.
 */
class Collection extends \Magento\Catalog\Block\Product\ListProduct
{

    /**
     * @var CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_productlists;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $_categoryRepository;

    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $stringUtils;

    /**
     * @var MpHelper
     */
    protected $mpHelper;

    /**
     * @var MpProductModel
     */
    protected $mpProductModel;

    /**
     * @param \Magento\Catalog\Block\Product\Context    $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Framework\Url\Helper\Data        $urlHelper
     * @param CollectionFactory                         $productCollectionFactory
     * @param \Magento\Catalog\Model\Layer\Resolver     $layerResolver
     * @param CategoryRepositoryInterface               $categoryRepository
     * @param \Magento\Framework\Stdlib\StringUtils     $stringUtils
     * @param MpHelper                                  $mpHelper
     * @param MpProductModel                            $mpProductModel
     * @param array                                     $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Stdlib\StringUtils $stringUtils,
        MpHelper $mpHelper,
        MpProductModel $mpProductModel,
        array $data = []
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_categoryRepository = $categoryRepository;
        $this->stringUtils = $stringUtils;
        $this->mpHelper = $mpHelper;
        $this->mpProductModel = $mpProductModel;
        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data
        );
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
                $title = __('Marketplace Seller Collection');
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
     * @return bool|\Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function _getProductCollection()
    {
        if (!$this->_productlists) {
            $paramData = $this->getRequest()->getParams();
            $partner = $this->getProfileDetail();
            try {
                $sellerId = $partner->getSellerId();
            } catch (\Exception $e) {
                $sellerId = 0;
            }

            $productname = $this->getRequest()->getParam('name');
            $querydata = $this->mpProductModel->create()
                        ->getCollection()
                        ->addFieldToFilter(
                            'seller_id',
                            ['eq' => $sellerId]
                        )
                        ->addFieldToFilter(
                            'status',
                            ['eq' => 1]
                        )
                        ->addFieldToSelect('mageproduct_id')
                        ->setOrder('mageproduct_id');

            $layer = $this->getLayer();

            $origCategory = null;
            if (isset($paramData['c']) || isset($paramData['cat'])) {
                try {
                    if (isset($paramData['c'])) {
                        $catId = $paramData['c'];
                    }
                    if (isset($paramData['cat'])) {
                        $catId = $paramData['cat'];
                    }
                    $category = $this->_categoryRepository->get($catId);
                } catch (\Exception $e) {
                    $category = null;
                }

                if ($category) {
                    $origCategory = $layer->getCurrentCategory();
                    $layer->setCurrentCategory($category);
                }
            }
            $collection = $layer->getProductCollection();
            $collection->addAttributeToSelect('*');
            $collection->addAttributeToFilter(
                'entity_id',
                ['in' => $querydata->getData()]
            );
            $this->prepareSortableFieldsByCategory($layer->getCurrentCategory());

            $this->_productlists = $collection;

            if ($origCategory) {
                $layer->setCurrentCategory($origCategory);
            }
            $toolbar = $this->getToolbarBlock();
            $this->configureProductToolbar($toolbar, $collection);

            $this->_eventManager->dispatch(
                'catalog_block_product_list_collection',
                ['collection' => $collection]
            );
        }
        $this->_productlists->getSize();

        return $this->_productlists;
    }

    /**
     * Configures the Toolbar block for sorting related data.
     *
     * @param ProductList\Toolbar $toolbar
     * @param ProductCollection $collection
     * @return void
     */
    public function configureProductToolbar(Toolbar $toolbar, ProductCollection $collection)
    {
        $availableOrders = $this->getAvailableOrders();
        if ($availableOrders) {
            $toolbar->setAvailableOrders($availableOrders);
        }
        $sortBy = $this->getSortBy();
        if ($sortBy) {
            $toolbar->setDefaultOrder($sortBy);
        }
        $defaultDirection = $this->getDefaultDirection();
        if ($defaultDirection) {
            $toolbar->setDefaultDirection($defaultDirection);
        }
        $sortModes = $this->getModes();
        if ($sortModes) {
            $toolbar->setModes($sortModes);
        }
        // set collection to toolbar and apply sort
        $toolbar->setCollection($collection);
        $this->setChild('toolbar', $toolbar);
    }

    public function getDefaultDirection()
    {
        return 'asc';
    }

    public function getSortBy()
    {
        return 'entity_id';
    }

    /**
     * Get Seller Profile Details
     *
     * @return \Webkul\Marketplace\Model\Seller | bool
     */
    public function getProfileDetail()
    {
        $helper = $this->mpHelper;
        return $helper->getProfileDetail(MpHelper::URL_TYPE_COLLECTION);
    }
}
