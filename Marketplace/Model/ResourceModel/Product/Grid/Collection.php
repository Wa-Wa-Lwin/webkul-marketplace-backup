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

namespace Webkul\Marketplace\Model\ResourceModel\Product\Grid;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Search\AggregationInterface;
use Webkul\Marketplace\Model\ResourceModel\Product\Collection as ProductCollection;

/**
 * Class Collection
 * Collection for displaying grid of marketplace product
 */
class Collection extends ProductCollection implements SearchResultInterface
{
    /**
     * @var AggregationInterface
     */
    protected $_aggregations;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param mixed|null $mainTable
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $eventPrefix
     * @param mixed $eventObject
     * @param mixed $resourceModel
     * @param string $model
     * @param null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $attribute,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        $model = \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $storeManager,
            $connection,
            $resource
        );
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->_init($model, $resourceModel);
        $this->attribute = $attribute;
        $this->setMainTable($mainTable);
    }

    /**
     * @return AggregationInterface
     */
    public function getAggregations()
    {
        return $this->_aggregations;
    }

    /**
     * @param AggregationInterface $aggregations
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        $this->_aggregations = $aggregations;
    }

    /**
     * Retrieve all ids for collection
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAllIds($limit = null, $offset = null)
    {
        return $this->getConnection()->fetchCol(
            $this->_getAllIdsSelect($limit, $offset),
            $this->_bindParams
        );
    }

    /**
     * Get search criteria.
     *
     * @return \Magento\Framework\Api\SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     */
    public function setSearchCriteria(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null
    ) {
        return $this;
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * Set items list.
     *
     * @param \Magento\Framework\Api\ExtensibleDataInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null)
    {
        return $this;
    }

    protected function _initSelect()
    {
        $this->addFilterToMap("product_name", "cpev.value");
        $this->addFilterToMap("product_status", "cpei.value");
        $this->addFilterToMap("mage_store_id", "cpev.store_id");
        $this->addFilterToMap("created_at", "main_table.created_at");
        $this->addFilterToMap("updated_at", "main_table.updated_at");
        $this->addFilterToMap("name", "cgf.name");

        parent::_initSelect();
    }
    /**
     * Join store relation table if there is store filter
     *
     * @return void
     */
    protected function _renderFiltersBefore()
    {
        $eavAttribute = $this->attribute;
        $proPriceAttrId = $eavAttribute->getIdByCode("catalog_product", "price");
        $proStatusAttrId = $eavAttribute->getIdByCode("catalog_product", "status");
        $proVisibilityAttrId = $eavAttribute->getIdByCode("catalog_product", "visibility");

        $customerGridFlat = $this->getTable('customer_grid_flat');
        $catalogProductEntityDecimal = $this->getTable('catalog_product_entity_decimal');
        $catalogProductEntityInt = $this->getTable('catalog_product_entity_int');
        $catalogInventoryStockItem = $this->getTable('cataloginventory_stock_item');

        $proAttrId = $eavAttribute->getIdByCode("catalog_product", "name");
        $catalogProductEntityVarchar = $this->getTable('catalog_product_entity_varchar');

        $this->getSelect()->join(
            $catalogProductEntityVarchar.' as cpev',
            'main_table.mageproduct_id = cpev.entity_id',
            ["product_name" => "value", "mage_store_id" => "store_id"]
        )->where("cpev.store_id = 0 AND cpev.attribute_id = ".$proAttrId);

        $this->getSelect()->join(
            $catalogProductEntityInt.' as cpei',
            'main_table.mageproduct_id = cpei.entity_id',
            ["product_status" => "value"]
        )->where("cpei.store_id = 0 AND cpei.attribute_id = ".$proStatusAttrId);

        $this->getSelect()->join(
            $catalogProductEntityInt.' as cpai',
            'main_table.mageproduct_id = cpai.entity_id',
            ["visibility" => "value"]
        )->where("cpai.store_id = 0 AND cpai.attribute_id = ".$proVisibilityAttrId);

        $this->getSelect()->join(
            $customerGridFlat.' as cgf',
            'main_table.seller_id = cgf.entity_id',
            ["name" => "name"]
        );
        $this->addFilterToMap("name", "cgf.name");

        $this->getSelect()->joinLeft(
            $catalogProductEntityDecimal.' as cped',
            'main_table.mageproduct_id = cped.entity_id and cped.store_id = 0 AND cped.attribute_id = '.$proPriceAttrId,
            ["product_price" => "value"]
        );
        $this->addFilterToMap("product_price", "cped.value");

        $this->getSelect()->join(
            $catalogInventoryStockItem.' as csi',
            'main_table.mageproduct_id = csi.product_id',
            ["qty" => "qty"]
        )->where("csi.website_id = 0 OR csi.website_id = 1");
        $flagsTable = $this->getTable('marketplace_productflags');
        $this->getSelect()->joinLeft(
            $flagsTable.' as flagTable',
            'main_table.mageproduct_id = flagTable.product_id',
            [
                    'flagcount' => 'count(flagTable.entity_id)'
                ]
        );
        $this->getSelect()->group('mageproduct_id');
        parent::_renderFiltersBefore();
    }
}
