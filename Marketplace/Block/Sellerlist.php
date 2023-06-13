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

use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Model\ProductFactory;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory;

/**
 * Webkul Marketplace Sellerlist Block.
 */
class Sellerlist extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $_filterProvider;

    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory
     */
    protected $_sellerlistCollectionFactory;

    /** @var \Webkul\Marketplace\Model\Seller */
    protected $sellerList;

    /**
     * @var MpHelper
     */
    protected $mpHelper;

    /**
     * @var ProductFactory
     */
    protected $productModel;

    /**
     * @var CollectionFactory
     */
    protected $sellerCollection;

    /**
     * @param Context                                    $context
     * @param array                                      $data
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     * @param MpHelper                                   $mpHelper
     * @param ProductFactory                             $productModel
     * @param CollectionFactory                          $sellerCollection
     */
    public function __construct(
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Catalog\Block\Product\Context $context,
        \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerlistCollectionFactory,
        MpHelper $mpHelper,
        ProductFactory $productModel,
        CollectionFactory $sellerCollection,
        array $data = []
    ) {
        $this->_sellerlistCollectionFactory = $sellerlistCollectionFactory;
        $this->_filterProvider = $filterProvider;
        $this->mpHelper = $mpHelper;
        $this->productModel = $productModel;
        $this->sellerCollection = $sellerCollection;
        parent::__construct($context, $data);
    }

    /**
     * @return bool|\Magento\Ctalog\Model\ResourceModel\Product\Collection
     */
    public function getSellerCollection()
    {
        if (!$this->sellerList) {
            $helper = $this->mpHelper;
            $paramData = $this->getRequest()->getParams();

            $sellerArr = [];

            $sellerProductColl = $this->productModel->create()
                                ->getCollection()
                                ->addFieldToFilter(
                                    'status',
                                    ['eq' => 1]
                                )
                                ->addFieldToSelect('seller_id')
                                ->distinct(true);
            $sellerArr = $sellerProductColl->getAllSellerIds();

            $storeCollection = $this->_sellerlistCollectionFactory
            ->create()
            ->addFieldToSelect(
                '*'
            )
            ->addFieldToFilter(
                'seller_id',
                ['in' => $sellerArr]
            )
            ->addFieldToFilter(
                'is_seller',
                ['eq' => 1]
            )->addFieldToFilter(
                'store_id',
                $helper->getCurrentStoreId()
            )->setOrder(
                'entity_id',
                'desc'
            );
            $storeSellerIDs = $storeCollection->getAllIds();
            $storeMainSellerIDs = $storeCollection->getAllSellerIds();

            $sellerArr = array_diff($sellerArr, $storeMainSellerIDs);

            $adminStoreCollection = $this->_sellerlistCollectionFactory
            ->create()
            ->addFieldToSelect(
                '*'
            )->addFieldToFilter(
                'seller_id',
                ['in' => $sellerArr]
            );
            if (!empty($storeSellerIDs)) {
                $adminStoreCollection->addFieldToFilter(
                    'entity_id',
                    ['nin' => $storeSellerIDs]
                );
            }
            $adminStoreCollection->addFieldToFilter(
                'is_seller',
                ['eq' => 1]
            )->addFieldToFilter(
                'store_id',
                0
            )->setOrder(
                'entity_id',
                'desc'
            );
            $adminStoreSellerIDs = $adminStoreCollection->getAllIds();

            $allSellerIDs = array_merge($storeSellerIDs, $adminStoreSellerIDs);

            $collection = $this->_sellerlistCollectionFactory
            ->create()
            ->addFieldToSelect(
                '*'
            )->addFieldToFilter(
                'entity_id',
                ['in' => $allSellerIDs]
            )->setOrder(
                'entity_id',
                'desc'
            );

            if (isset($paramData['shop']) && $paramData['shop']) {
                $collection->addFieldToFilter(
                    [
                        "shop_title",
                        "shop_url"
                    ],
                    [
                        ["like"=>"%".$paramData['shop']."%"],
                        ["like"=>"%".$paramData['shop']."%"]
                    ]
                );
            }
            $websiteId = $helper->getWebsiteId();
            $joinTable = $this->sellerCollection->create()->getTable('customer_grid_flat');
            $collection->getSelect()->join(
                $joinTable.' as cgf',
                'main_table.seller_id = cgf.entity_id AND website_id= '.$websiteId
            );
            $this->sellerList = $collection;
        }

        return $this->sellerList;
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getSellerCollection()) {
            $pager = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'marketplace.seller.list.pager'
            )
            ->setAvailableLimit([4 => 4, 8 => 8, 16 => 16])
            ->setCollection(
                $this->getSellerCollection()
            );
            $this->setChild('pager', $pager);
            $this->getSellerCollection()->load();
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * Prepare HTML content.
     *
     * @return string
     */
    public function getCmsFilterContent($value = '')
    {
        $html = $this->_filterProvider->getPageFilter()->filter($value);

        return $html;
    }
}
