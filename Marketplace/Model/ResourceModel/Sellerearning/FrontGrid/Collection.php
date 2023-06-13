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

namespace Webkul\Marketplace\Model\ResourceModel\Sellerearning\FrontGrid;

use Magento\Framework\Api\Search\SearchResultInterface as ApiSearchResultInterface;
use Webkul\Marketplace\Model\ResourceModel\Saleslist\Collection as SaleslistCollection;
use Magento\Framework\Search\AggregationInterface as SearchAggregationInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb as ResourceModelAbstractDb;
use Webkul\Marketplace\Helper\Data as HelperData;

/**
 * Webkul\Marketplace\Model\ResourceModel\Saleslist\Grid\Collection Class
 * Collection for displaying grid of marketplace Saleslist.
 */
class Collection extends SaleslistCollection implements ApiSearchResultInterface
{
    /**
     * Period format
     *
     * @var string
     */
    protected $_periodFormat;

    /**
     * @var SearchAggregationInterface
     */
    protected $aggregations;

    /**
     * @var HelperData
     */
    public $helperData;

    /**
     * @var HttpRequest
     */
    public $httpRequest;

    /**
     * @param EntityFactoryInterface                               $entityFactoryInterface
     * @param LoggerInterface                                      $loggerInterface
     * @param FetchStrategyInterface                               $fetchStrategyInterface
     * @param EventManagerInterface                                $eventManagerInterface
     * @param StoreManagerInterface                                $storeManagerInterface
     * @param HelperData                                           $helperData
     * @param mixed|null                                           $mainTable
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $eventPrefix
     * @param mixed                                                $eventObject
     * @param mixed                                                $resourceModel
     * @param string                                               $model
     * @param null                                                 $connection
     * @param ResourceModelAbstractDb|null                         $resource
     */
    public function __construct(
        EntityFactoryInterface $entityFactoryInterface,
        LoggerInterface $loggerInterface,
        FetchStrategyInterface $fetchStrategyInterface,
        EventManagerInterface $eventManagerInterface,
        StoreManagerInterface $storeManagerInterface,
        HelperData $helperData,
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        $model = \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class,
        $connection = null,
        ResourceModelAbstractDb $resource = null
    ) {
        $this->helperData = $helperData;
        parent::__construct(
            $entityFactoryInterface,
            $loggerInterface,
            $fetchStrategyInterface,
            $eventManagerInterface,
            $storeManagerInterface,
            $connection,
            $resource
        );
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->_init($model, $resourceModel);
        $this->setMainTable($mainTable);
    }

    /**
     * @return SearchAggregationInterface
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @param SearchAggregationInterface $aggregationsData
     *
     * @return $this
     */
    public function setAggregations($aggregationsData)
    {
        $this->aggregations = $aggregationsData;
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
        return $this;
    }

    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
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
     *
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
     *
     * @return $this
     */
    public function setItems(array $items = null)
    {
        return $this;
    }

    /**
     * Join store relation table if there is store filter
     *
     * @return void
     */
    protected function _renderFiltersBefore()
    {
        try {
            $from = null;
            $to = null;
            $paramData = $this->helperData->getParams();
            $this->updatePeriodFormat();
            $filterDateFrom = $paramData['from'] ?? '';
            $filterDateTo = $paramData['to'] ?? '';
            if ($filterDateTo) {
                $todate = date_create($filterDateTo);
                $to = date_format($todate, 'Y-m-d 23:59:59');
            }
            if (!$to) {
                $to = date('Y-m-d 23:59:59');
            }
            if ($filterDateFrom) {
                $fromdate = date_create($filterDateFrom);
                $from = date_format($fromdate, 'Y-m-d H:i:s');
            }
            if (!$from) {
                $from = date('Y-m-d 23:59:59', strtotime($from));
            }
            $sellerId = $this->helperData->getCustomerId();
            $this->getSelect()->where("main_table.seller_id = ".$sellerId);
            if ($from && $to) {
                $this->getSelect()->where(
                    "main_table.created_at BETWEEN '".$from."' AND '".$to."'"
                );
            }
            $this->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
            $this->getSelect()->columns(
                [
                    'COUNT(DISTINCT order_id) as order_count',
                    'SUM(order_item_id) as item_count',
                    'SUM(total_commission) as total_commission',
                    'SUM(actual_seller_amount - applied_coupon_amount) as total_seller_amount',
                    'SUM(applied_coupon_amount) as total_discount_amount',
                    'SUM(total_tax) as total_tax_amount',
                    'SUM(total_amount) as total_amount',
                    'created_at'
                ]
            );
            $this->getSelect()->group($this->_periodFormat);
        } catch (\Exception $e) {
            $sellerId = $this->helperData->getCustomerId();
            $this->getSelect()->where("main_table.seller_id = ".$sellerId."
            AND main_table.order_id = 0");
            $this->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
            $this->getSelect()->columns(
                [
                    'COUNT(DISTINCT order_id) as order_count',
                    'SUM(order_item_id) as item_count',
                    'SUM(total_commission) as total_commission',
                    'SUM(actual_seller_amount - applied_coupon_amount) as total_seller_amount',
                    'SUM(applied_coupon_amount) as total_discount_amount',
                    'SUM(total_tax) as total_tax_amount',
                    'SUM(total_amount) as total_amount',
                    'created_at'
                ]
            );
            $this->getSelect()->group($this->_periodFormat);
            $this->helperData->logDataInLogger("Block_Product_ProductList getAllProducts : ".$e->getMessage());
        }
        parent::_renderFiltersBefore();
    }

    /**
     * updatePeriodFormat function
     *
     * @return void
     */
    protected function updatePeriodFormat()
    {
        $paramData = $this->helperData->getParams();
        $this->_period = $paramData['period'] ?? '';
        $connection = $this->getConnection();
        if ('month' == $this->_period) {
            $this->_periodFormat = $connection->getDateFormatSql('created_at', '%Y-%m');
        } elseif ('year' == $this->_period) {
            $this->_periodFormat = $connection->getDateExtractSql(
                'created_at',
                \Magento\Framework\DB\Adapter\AdapterInterface::INTERVAL_YEAR
            );
        } else {
            $this->_periodFormat = $connection->getDateFormatSql('created_at', '%Y-%m-%d');
        }
    }
}
