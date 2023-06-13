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
 * Webkul Marketplace Landing Page Block
 */
use Magento\Catalog\Model\Product;
use Magento\Sales\Model\Order;
use Webkul\Marketplace\Model\Seller;
use Magento\Customer\Model\Customer;
use Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory as MpOrdersCollectionFactory;
use Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory as MpSaleslistCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class Marketplace extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $_filterProvider;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $entityAttribute;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $helper;

    /**
     * @var MpOrdersCollectionFactory
     */
    protected $mpOrdersCollectionFactory;

    /**
     * @var MpSaleslistCollectionFactory
     */
    protected $mpSaleslistCollectionFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $entityAttribute
     * @param \Webkul\Marketplace\Helper\Data $helper
     * @param MpOrdersCollectionFactory $mpOrdersCollectionFactory
     * @param MpSaleslistCollectionFactory $mpSaleslistCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $entityAttribute,
        \Webkul\Marketplace\Helper\Data $helper,
        MpOrdersCollectionFactory $mpOrdersCollectionFactory,
        MpSaleslistCollectionFactory $mpSaleslistCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->imageHelper = $context->getImageHelper();
        $this->filterProvider = $filterProvider;
        $this->resource = $resource;
        $this->entityAttribute = $entityAttribute;
        $this->helper = $helper;
        $this->mpOrdersCollectionFactory = $mpOrdersCollectionFactory;
        $this->mpSaleslistCollectionFactory = $mpSaleslistCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        parent::__construct($context, $data);
    }

    public function imageHelperObj()
    {
        return $this->imageHelper;
    }

    /**
     * Prepare HTML content.
     *
     * @return string
     */
    public function getCmsFilterContent($value = '')
    {
        return $this->filterProvider->getPageFilter()->filter($value);
    }

    public function getStoreId()
    {
        if (count($this->helper->getAllStores()) == 1 && count($this->helper->getAllWebsites()) == 1) {
            $storeId = 0;
        } else {
            $storeId = $this->helper->getCurrentStoreId();
        }

        return $storeId;
    }

    public function getBestSaleSellers()
    {
        $marketplaceUserdata = $this->resource->getTableName('marketplace_userdata');
        $catalogProductEntityInt = $this->resource->getTableName('catalog_product_entity_int');
        $marketplaceProduct = $this->resource->getTableName('marketplace_product');
        $catalogProductWebsite = $this->resource->getTableName('catalog_product_website');
        $proAttId = $this->entityAttribute->getIdByCode('catalog_product', 'visibility');

        $helper = $this->helper;
        $sellersOrder = $this->mpOrdersCollectionFactory->create()->addFieldToSelect('seller_id');
        $storeId = $this->getStoreId();
        $sellersOrder->joinSellerTable();
        $sellersOrder->addActiveSellerFilter();
        $sellersOrder->addFieldToFilter('invoice_id', ['neq' => 0]);
        $sellersOrder->getSelect()->group('main_table.seller_id');
        $sellersOrder = $helper->joinCustomer($sellersOrder);
        $sellersOrder->resetColumns();
        $sellersOrder->getSelect()->columns('seller_id');
        
        $websiteId = $helper->getWebsiteId();
        $joinTable = $this->resource->getTableName('customer_grid_flat');

        $sellerArr = [];
        $sellerIdsArr = [];
        $sellerCountArr = [];
        foreach ($sellersOrder as $value) {
            $sellerId = $value['seller_id'];
            if ($sellerHelperProCount = $helper->getSellerProCount($sellerId)) {
                $sellerArr[$sellerId] = [];
                array_push($sellerIdsArr, $sellerId);
                $sellerCountArr[$sellerId] = [];
                array_push($sellerCountArr[$sellerId], $sellerHelperProCount);
                $sellerProducts = $this->mpSaleslistCollectionFactory->create()
                ->addFieldToSelect('mageproduct_id')
                ->addFieldToSelect('magequantity')
                ->addFieldToSelect('seller_id')
                ->addFieldToSelect('cpprostatus');
                $sellerProducts->getSelect()
                ->join(
                    ['mpro' => $marketplaceProduct],
                    'mpro.mageproduct_id = main_table.mageproduct_id',
                    ['status' => 'status']
                )->where(
                    'main_table.seller_id='.$sellerId.' 
                    AND main_table.cpprostatus=1 
                    AND mpro.status = 1'
                );
                $sellerProducts->getSelect()
                ->columns('SUM(magequantity) as countOrderedProduct')
                ->group('mageproduct_id');
                $sellerProducts->setOrder('countOrderedProduct', 'DESC');

                $sellerProducts->getSelect()
                ->join(
                    ['cpei' => $catalogProductEntityInt],
                    'cpei.entity_id = main_table.mageproduct_id',
                    ['value' => 'value']
                )->where(
                    'cpei.value=4 
                    AND cpei.attribute_id = '.$proAttId.' 
                    AND cpei.store_id = '.$storeId
                );

                $sellerProducts->getSelect()->limit(3);
                foreach ($sellerProducts as $sellerProduct) {
                    array_push(
                        $sellerArr[$sellerId],
                        $sellerProduct['mageproduct_id']
                    );
                }
                if ((count($sellerProducts) < 3) && $storeId != 0) {
                    $sellerProducts = $this->mpSaleslistCollectionFactory->create()
                    ->addFieldToSelect('mageproduct_id')
                    ->addFieldToSelect('magequantity')
                    ->addFieldToSelect('seller_id')
                    ->addFieldToSelect('cpprostatus');
                    $sellerProducts->getSelect()
                    ->join(
                        ['mpro' => $marketplaceProduct],
                        'mpro.mageproduct_id = main_table.mageproduct_id',
                        ['status' => 'status']
                    );
                    if (count($sellerArr[$sellerId])) {
                        $sellerProducts->getSelect()->where(
                            'main_table.seller_id='.$sellerId.'
                            AND main_table.mageproduct_id NOT IN ('.implode(',', $sellerArr[$sellerId]).')
                            AND main_table.cpprostatus=1
                            AND mpro.status = 1'
                        );
                    } else {
                        $sellerProducts->getSelect()->where(
                            'main_table.seller_id='.$sellerId.'
                            AND main_table.cpprostatus=1
                            AND mpro.status = 1'
                        );
                    }
                    $sellerProducts->getSelect()
                    ->columns('SUM(magequantity) as countOrderedProduct')
                    ->group('mageproduct_id');
                    $sellerProducts->setOrder('countOrderedProduct', 'DESC');
    
                    $sellerProducts->getSelect()
                    ->join(
                        ['cpei' => $catalogProductEntityInt],
                        'cpei.entity_id = main_table.mageproduct_id',
                        ['value' => 'value']
                    )->where(
                        'cpei.value=4 
                        AND cpei.attribute_id = '.$proAttId.' 
                        AND cpei.store_id = 0'
                    );
                    $remaingCount = 3 - count($sellerArr[$sellerId]);
                    $sellerProducts->getSelect()->limit($remaingCount);
                    foreach ($sellerProducts as $sellerProduct) {
                        array_push(
                            $sellerArr[$sellerId],
                            $sellerProduct['mageproduct_id']
                        );
                    }
                }

                if (count($sellerArr[$sellerId]) < 3) {
                    $sellerProCount = count($sellerArr[$sellerId]);
                    $sellerProductColl = $this->productCollectionFactory->create()
                    ->addFieldToFilter(
                        'status',
                        ['eq' => 1]
                    )->addFieldToFilter(
                        'visibility',
                        ['eq' => 4]
                    )
                    ->addFieldToFilter(
                        'entity_id',
                        ['nin' => $sellerArr[$sellerId]]
                    );
                    $sellerProductColl->getSelect()
                    ->join(
                        ['cpw' => $catalogProductWebsite],
                        'cpw.product_id = e.entity_id'
                    )->where(
                        'cpw.website_id = '.$helper->getWebsiteId()
                    );
                    $sellerProductColl->getSelect()
                    ->join(
                        ['mpro' => $marketplaceProduct],
                        'mpro.mageproduct_id = e.entity_id',
                        [
                            'seller_id' => 'seller_id',
                            'mageproduct_id' => 'mageproduct_id'
                        ]
                    )->where(
                        'mpro.seller_id = '.$sellerId
                    );
                    $sellerProductColl->getSelect()->limit(3);
                    foreach ($sellerProductColl as $value) {
                        if ($sellerProCount < 3) {
                            array_push(
                                $sellerArr[$value['seller_id']],
                                $value['entity_id']
                            );
                            ++$sellerProCount;
                        }
                    }
                }
            }
        }
        if (count($sellerArr) != 4) {
            $i = count($sellerArr);
            $countProArr = [];
            $sellerProductColl = $this->productCollectionFactory->create()
            ->addFieldToFilter(
                'status',
                ['eq' => 1]
            )->addFieldToFilter(
                'visibility',
                ['eq' => 4]
            );
            $sellerProductColl->getSelect()
            ->join(
                ['cpw' => $catalogProductWebsite],
                'cpw.product_id = e.entity_id'
            )->where(
                'cpw.website_id = '.$helper->getWebsiteId()
            );
            $sellerProductColl->getSelect()
            ->join(
                ['mpro' => $marketplaceProduct],
                'mpro.mageproduct_id = e.entity_id',
                [
                    'seller_id' => 'seller_id',
                    'mageproduct_id' => 'mageproduct_id'
                ]
            );
            if (count($sellerArr)) {
                $sellerProductColl->getSelect()->join(
                    ['mmu' => $marketplaceUserdata],
                    'mmu.seller_id = mpro.seller_id',
                    ['is_seller' => 'is_seller']
                )->where(
                    'mmu.is_seller = 1 
                    AND mmu.seller_id NOT IN ('.implode(',', array_keys($sellerArr)).')'
                );
            } else {
                $sellerProductColl->getSelect()->join(
                    ['mmu' => $marketplaceUserdata],
                    'mmu.seller_id = mpro.seller_id',
                    ['is_seller' => 'is_seller']
                )->where(
                    'mmu.is_seller = 1'
                );
            }

            if ($helper->getCustomerSharePerWebsite()) {
                $sellerProductColl->getSelect()->join(
                    $joinTable.' as cgf',
                    'mpro.seller_id = cgf.entity_id AND cgf.website_id= '.$websiteId
                );
            } else {
                $sellerProductColl->getSelect()->join(
                    $joinTable.' as cgf',
                    'mpro.seller_id = cgf.entity_id'
                );
            }

            $sellerProductColl->getSelect()
                             ->columns('COUNT(*) as countOrder')
                             ->group('seller_id');
            foreach ($sellerProductColl as $value) {
                if (!isset($countProArr[$value['seller_id']])) {
                    $countProArr[$value['seller_id']] = [];
                }
                $countProArr[$value['seller_id']] = $value['countOrder'];
            }

            arsort($countProArr);

            foreach ($countProArr as $procountSellerId => $procount) {
                if ($i <= 4) {
                    if ($sellerHelperProCount = $helper->getSellerProCount($procountSellerId)) {
                        array_push($sellerIdsArr, $procountSellerId);

                        if (!isset($sellerCountArr[$procountSellerId])) {
                            $sellerCountArr[$procountSellerId] = [];
                        }
                        array_push($sellerCountArr[$procountSellerId], $sellerHelperProCount);

                        if (!isset($sellerArr[$procountSellerId])) {
                            $sellerArr[$procountSellerId] = [];
                        }
                        $sellerProductColl = $this->productCollectionFactory->create()
                        ->addFieldToFilter(
                            'status',
                            ['eq' => 1]
                        )->addFieldToFilter(
                            'visibility',
                            ['eq' => 4]
                        );
                        $sellerProductColl->getSelect()
                        ->join(
                            ['cpw' => $catalogProductWebsite],
                            'cpw.product_id = e.entity_id'
                        )->where(
                            'cpw.website_id = '.$helper->getWebsiteId()
                        );
                        $sellerProductColl->getSelect()
                        ->join(
                            ['mpro' => $marketplaceProduct],
                            'mpro.mageproduct_id = e.entity_id',
                            [
                                'seller_id' => 'seller_id',
                                'mageproduct_id' => 'mageproduct_id'
                            ]
                        )->where(
                            'mpro.seller_id = '.$procountSellerId
                        );
                        $sellerProductColl->getSelect()->limit(3);
                        foreach ($sellerProductColl as $value) {
                            array_push($sellerArr[$procountSellerId], $value['mageproduct_id']);
                        }
                        if ((count($sellerProductColl) < 3) && $storeId != 0) {
                            $sellerProductColl = $this->productCollectionFactory->create()
                            ->addFieldToFilter(
                                'status',
                                ['eq' => 1]
                            )->addFieldToFilter(
                                'visibility',
                                ['eq' => 4]
                            );
                            $sellerProductColl->getSelect()
                            ->join(
                                ['cpw' => $catalogProductWebsite],
                                'cpw.product_id = e.entity_id'
                            )->where(
                                'cpw.website_id = '.$helper->getWebsiteId()
                            );
                            if (count($sellerArr[$procountSellerId])) {
                                $sellerProductColl->addFieldToFilter(
                                    'entity_id',
                                    ['nin' => $sellerArr[$procountSellerId]]
                                );
                            }
                            $sellerProductColl->getSelect()
                            ->join(
                                ['mpro' => $marketplaceProduct],
                                'mpro.mageproduct_id = e.entity_id',
                                [
                                    'seller_id' => 'seller_id',
                                    'mageproduct_id' => 'mageproduct_id'
                                ]
                            )->where(
                                'mpro.seller_id = '.$procountSellerId
                            );
                            $remaingCount = 3 - count($sellerArr[$procountSellerId]);
                            $sellerProductColl->getSelect()->limit($remaingCount);
                            foreach ($sellerProductColl as $value) {
                                array_push(
                                    $sellerArr[$procountSellerId],
                                    $value['mageproduct_id']
                                );
                            }
                        }
                    }
                }
                ++$i;
            }
        }
        $sellerProfileArr =  [];
        foreach ($sellerIdsArr as $sellerId) {
            $sellerData = $helper->getSellerCollectionObj($sellerId);
            foreach ($sellerData as $sellerDataResult) {
                $sellerId = $sellerDataResult->getSellerId();
                $sellerProfileArr[$sellerId] = [];
                $profileurl = $sellerDataResult->getShopUrl();
                $shoptitle = $sellerDataResult->getShopTitle();
                $logo = $sellerDataResult->getLogoPic()??"noimage.png";
                array_push(
                    $sellerProfileArr[$sellerId],
                    [
                        'profileurl'=>$profileurl,
                        'shoptitle'=>$shoptitle,
                        'logo'=>$logo
                    ]
                );
            }
        }

        return [$sellerArr, $sellerProfileArr, $sellerCountArr];
    }

    public function getProduct($productId)
    {
        return $this->productRepository->getById($productId);
    }
}
