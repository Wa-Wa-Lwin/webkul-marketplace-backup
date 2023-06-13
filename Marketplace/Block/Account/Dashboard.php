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

namespace Webkul\Marketplace\Block\Account;

use Webkul\Marketplace\Model\ResourceModel\Saleperpartner\CollectionFactory as SalesPartnerCollection;

class Dashboard extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Model\Order\ItemRepository
     */
    protected $orderItemRepository;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var \Webkul\Marketplace\Helper\Orders
     */
    protected $orderHelper;

    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory
     */
    protected $mpSaleslistCollectionFactory;

    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory
     */
    protected $mpOrderCollectionFactory;

    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory
     */
    protected $mpProductCollectionFactory;

    /**
     * @var SalesPartnerCollection
     */
    protected $mpSalePerPartnerCollectionFactory;

    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Feedback\CollectionFactory
     */
    protected $mpFeedbackCollectionFactory;

    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Sales\Model\Order\ItemRepository $orderItemRepository
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
     * @param \Webkul\Marketplace\Helper\Orders $orderHelper
     * @param \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory $mpSaleslistCollectionFactory
     * @param \Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory $mpOrderCollectionFactory
     * @param \Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory $mpProductCollectionFactory
     * @param SalesPartnerCollection $mpSalePerPartnerCollectionFactory
     * @param \Webkul\Marketplace\Model\ResourceModel\Feedback\CollectionFactory $mpFeedbackCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\Order\ItemRepository $orderItemRepository,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Webkul\Marketplace\Helper\Orders $orderHelper,
        \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory $mpSaleslistCollectionFactory,
        \Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory $mpOrderCollectionFactory,
        \Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory $mpProductCollectionFactory,
        SalesPartnerCollection $mpSalePerPartnerCollectionFactory,
        \Webkul\Marketplace\Model\ResourceModel\Feedback\CollectionFactory $mpFeedbackCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        array $data = []
    ) {
        $this->customer = $customer;
        $this->customerSession = $customerSession;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->orderHelper = $orderHelper;
        $this->mpSaleslistCollectionFactory = $mpSaleslistCollectionFactory;
        $this->mpOrderCollectionFactory = $mpOrderCollectionFactory;
        $this->mpProductCollectionFactory = $mpProductCollectionFactory;
        $this->mpSalePerPartnerCollectionFactory = $mpSalePerPartnerCollectionFactory;
        $this->mpFeedbackCollectionFactory = $mpFeedbackCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('Seller Dashboard'));
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    public function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

    /**
     * @return \Webkul\Marketplace\Model\ResourceModel\Saleslist\Collection
     */
    public function getCollection()
    {
        if (!($customerId = $this->getCustomerId())) {
            return false;
        }

        $paramData = $this->getRequest()->getParams();
        $filterOrderid = '';
        $filterOrderstatus = '';
        $filterDataTo = '';
        $filterDataFrom = '';
        $from = null;
        $to = null;

        if (isset($paramData['s'])) {
            $filterOrderid = $paramData['s'] != '' ? $paramData['s'] : '';
        }
        if (isset($paramData['orderstatus'])) {
            $filterOrderstatus = $paramData['orderstatus'] != '' ? $paramData['orderstatus'] : '';
        }
        if (isset($paramData['from_date'])) {
            $filterDataFrom = $paramData['from_date'] != '' ? $paramData['from_date'] : '';
        }
        if (isset($paramData['to_date'])) {
            $filterDataTo = $paramData['to_date'] != '' ? $paramData['to_date'] : '';
        }

        $orderids = $this->getOrderIdsArray($customerId, $filterOrderstatus);

        $ids = $this->getEntityIdsArray($orderids);

        $collection = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToSelect(
            '*'
        )->addFieldToFilter(
            'entity_id',
            ['in' => $ids]
        );

        if ($filterDataTo) {
            $todate = date_create($filterDataTo);
            $to = date_format($todate, 'Y-m-d 23:59:59');
        }
        if ($filterDataFrom) {
            $fromdate = date_create($filterDataFrom);
            $from = date_format($fromdate, 'Y-m-d H:i:s');
        }

        if ($filterOrderid) {
            $collection->addFieldToFilter(
                'magerealorder_id',
                ['eq' => $filterOrderid]
            );
        }
        if ($from && $to) {
            $collection->addFieldToFilter(
                'created_at',
                ['datetime' => true, 'from' => $from, 'to' => $to]
            );
        }

        $collection->setOrder(
            'created_at',
            'desc'
        );
        $collection->getSellerOrderCollection();
        $collection->setPageSize(5);

        return $collection;
    }

    public function getOrderIdsArray($customerId = '', $filterOrderstatus = '')
    {
        $orderids = [];

        $collectionOrders = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $customerId]
        )
        ->addFieldToSelect('order_id')
        ->distinct(true);

        foreach ($collectionOrders as $collectionOrder) {
            $tracking = $this->orderHelper->getOrderinfo($collectionOrder->getOrderId());

            if ($tracking) {
                if ($filterOrderstatus) {
                    if ($tracking->getIsCanceled()) {
                        if ($filterOrderstatus == 'canceled') {
                            array_push($orderids, $collectionOrder->getOrderId());
                        }
                    } else {
                        $tracking = $this->orderRepository->get($collectionOrder->getOrderId());
                        if ($tracking->getStatus() == $filterOrderstatus) {
                            array_push($orderids, $collectionOrder->getOrderId());
                        }
                    }
                } else {
                    array_push($orderids, $collectionOrder->getOrderId());
                }
            }
        }

        return $orderids;
    }

    public function getEntityIdsArray($orderids = [])
    {
        $ids = [];
        foreach ($orderids as $orderid) {
            $collectionIds = $this->mpSaleslistCollectionFactory->create()
            ->addFieldToFilter(
                'order_id',
                ['eq' => $orderid]
            )
            ->addFieldToFilter('parent_item_id', ['null' => 'true'])
            ->setOrder('entity_id', 'DESC')
            ->setPageSize(1);
            foreach ($collectionIds as $collectionId) {
                $autoid = $collectionId->getId();
                array_push($ids, $autoid);
            }
        }

        return $ids;
    }

    public function getDateDetail()
    {
        $sellerId = $this->getCustomerId();

        $collection = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        )
        ->addFieldToFilter(
            'order_id',
            ['neq' => 0]
        )
        ->addFieldToFilter(
            'paid_status',
            ['neq' => 2]
        );
        $collection1 = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        )
        ->addFieldToFilter(
            'order_id',
            ['neq' => 0]
        )
        ->addFieldToFilter(
            'paid_status',
            ['neq' => 2]
        );
        $collection2 = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        )
        ->addFieldToFilter(
            'order_id',
            ['neq' => 0]
        )
        ->addFieldToFilter(
            'paid_status',
            ['neq' => 2]
        );
        $collection3 = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        )
        ->addFieldToFilter(
            'order_id',
            ['neq' => 0]
        )
        ->addFieldToFilter(
            'paid_status',
            ['neq' => 2]
        );

        $firstDayOfWeek = date('Y-m-d', strtotime('Last Monday', time()));

        $lastDayOfWeek = date('Y-m-d', strtotime('Next Sunday', time()));

        $month = $collection1->addFieldToFilter(
            'created_at',
            [
                'datetime' => true,
                'from' => date('Y-m').'-01 00:00:00',
                'to' => date('Y-m').'-31 23:59:59',
            ]
        );

        $week = $collection2->addFieldToFilter(
            'created_at',
            [
                'datetime' => true,
                'from' => $firstDayOfWeek.' 00:00:00',
                'to' => $lastDayOfWeek.' 23:59:59',
            ]
        );

        $day = $collection3->addFieldToFilter(
            'created_at',
            [
                'datetime' => true,
                'from' => date('Y-m-d').' 00:00:00',
                'to' => date('Y-m-d').' 23:59:59',
            ]
        );

        $sale = 0;

        $data1['year'] = $sale;

        $sale1 = 0;
        foreach ($day as $record1) {
            $sale1 = $sale1 + $record1->getActualSellerAmount();
        }
        $data1['day'] = $sale1;

        $sale2 = 0;
        foreach ($month as $record2) {
            $sale2 = $sale2 + $record2->getActualSellerAmount();
        }
        $data1['month'] = $sale2;

        $sale3 = 0;
        foreach ($week as $record3) {
            $sale3 = $sale3 + $record3->getActualSellerAmount();
        }
        $data1['week'] = $sale3;

        $temp = 0;
        foreach ($collection as $record) {
            $temp = $temp + $record->getActualSellerAmount();
        }
        $data1['totalamount'] = $temp;

        return $data1;
    }

    public function getpronamebyorder($orderId)
    {
        $orderHelper = $this->orderHelper;
        $sellerId = $this->getCustomerId();
        $collection = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ->addFieldToFilter(
            'order_id',
            $orderId
        )
        ->addFieldToFilter('parent_item_id', ['null' => 'true']);
        $productName = '';
        foreach ($collection as $res) {
            if ($res->getParentItemId()) {
                continue;
            }
            $productName = $orderHelper->getOrderedProductName($res, $productName);
        }

        return $productName;
    }

    public function getPricebyorder($orderId)
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->mpSaleslistCollectionFactory->create();
        $collection->addFieldToFilter('main_table.seller_id', $sellerId);
        $collection->addFieldToFilter('main_table.order_id', $orderId);
        $collection->getPricebyorderData();
        $name = '';
        $actualSellerAmount = 0;
        foreach ($collection as $coll) {
            // calculate order actual_seller_amount in base currency
            $appliedCouponAmount = $coll['applied_coupon_amount']*1;
            $shippingAmount = $coll['shipping_charges']*1;
            $refundedShippingAmount = $coll['refunded_shipping_charges']*1;
            $totalshipping = $shippingAmount - $refundedShippingAmount;
            $vendorTaxAmount = $coll['total_tax']*1;
            if ($coll['actual_seller_amount'] * 1) {
                $taxShippingTotal = $vendorTaxAmount + $totalshipping;
                $actualSellerAmount += $coll['actual_seller_amount'] + $taxShippingTotal;
            } else {
                if ($totalshipping * 1) {
                    $actualSellerAmount += $totalshipping;
                }
            }
        }
        return $actualSellerAmount;
    }

    public function getTopSaleProducts()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ->addFieldToFilter(
            'parent_item_id',
            ['null' => 'true']
        )
        ->getAllOrderProducts();
        $name = '';
        $resultData = [];
        foreach ($collection as $coll) {
            try {
                $item = $this->orderItemRepository->get($coll['order_item_id']);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                continue;
            }
            $product = $item->getProduct();
            if ($product) {
                $resultData[$coll->getId()]['name'] = $product->getName();
                $resultData[$coll->getId()]['url'] = $product->getProductUrl();
                $resultData[$coll->getId()]['qty'] = $coll['qty'];
            } else {
                $resultData[$coll->getId()]['name'] = $item->getName();
                $resultData[$coll->getId()]['url'] = '';
                $resultData[$coll->getId()]['qty'] = $coll['qty'];
            }
        }
        return $resultData;
    }

    public function getTopSaleCategories()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ->addFieldToFilter(
            'parent_item_id',
            ['null' => 'true']
        )
        ->getAllOrderProducts();
        $name = '';
        $resultData = [];
        $catArr = [];
        $totalOrderedProducts = 0;
        foreach ($collection as $coll) {
            $totalOrderedProducts = $totalOrderedProducts + $coll['qty'];
        }
        $collection = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ->addFieldToFilter(
            'parent_item_id',
            ['null' => 'true']
        );
        foreach ($collection as $coll) {
            try {
                $item = $this->orderItemRepository->get($coll['order_item_id']);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                continue;
            }
            $product = $item->getProduct();
            if ($product) {
                $productCategories = $product->getCategoryIds();
                foreach ($productCategories as $proCategory) {
                    if (!isset($catArr[$proCategory])) {
                        $catArr[$proCategory] = $coll['magequantity'];
                    } else {
                        $catArr[$proCategory] = $catArr[$proCategory] + $coll['magequantity'];
                    }
                }
            }
        }
        $categoryArr = [];
        $percentageArr = [];
        foreach ($catArr as $key => $value) {
            if ($value) {
                $percentageArr[$key] = round((($value * 100) / $totalOrderedProducts), 2);
            } else {
                $percentageArr[$key] = 0;
            }
            try {
                $categoryArr[$key] = $this->categoryRepository->get($key)->getName();
            } catch (\Exception $e) {
                unset($categoryArr[$key]);
            }
        }
        $resultData['percentage_arr'] = $percentageArr;
        $resultData['category_arr'] = $categoryArr;
        return $resultData;
    }

    public function getTotalSaleColl($value = '')
    {
        $sellerId = $this->getCustomerId();

        $collection = $this->mpSalePerPartnerCollectionFactory->create();
        $collection->addFieldToFilter('seller_id', ['eq' => $sellerId]);

        return $collection;
    }

    /**
     * Give the current url of recently viewed page.
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->_urlBuilder->getCurrentUrl();
    }

    public function getReviewcollection($value = '')
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->mpFeedbackCollectionFactory->create();
        $collection->addFieldToFilter('seller_id', ['eq' => $sellerId]);
        $collection->addFieldToFilter('status', ['eq' => 1]);
        $collection->setOrder('created_at', 'desc');
        $collection->setPageSize(5);
        $collection->setCurPage(1);
        return $collection;
    }

    public function getMainOrder($orderId)
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('entity_id', ['eq' => $orderId]);
        foreach ($collection as $res) {
            return $res;
        }

        return [];
    }

    public function getOrderedPricebyorder($currencyRate, $basePrice)
    {
        return $basePrice * $currencyRate;
    }

    public function getTotalOrders()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->mpOrderCollectionFactory->create();
        $collection->addFieldToFilter('seller_id', $sellerId);
        $salesOrder = $collection->getTable('sales_order');
        $collection->getSelect()->join(
            $salesOrder.' as so',
            'main_table.order_id = so.entity_id',
            ["order_approval_status" => "order_approval_status"]
        )->where("so.order_approval_status=1");
        return count($collection);
    }

    public function getPendingOrders()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ->getTotalOrders()
        ->getSellerOrderCollection();
        $collection->addFieldToFilter(
            'status',
            'pending'
        );
        return count($collection);
    }

    public function getProcessingOrders()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ->getTotalOrders()
        ->getSellerOrderCollection();
        $collection->addFieldToFilter(
            'status',
            'processing'
        );
        return count($collection);
    }

    public function getCompletedOrders()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ->getTotalOrders()
        ->getSellerOrderCollection();
        $collection->addFieldToFilter(
            'status',
            'complete'
        );
        return count($collection);
    }

    public function getTotalProducts()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->mpProductCollectionFactory->create();
        $collection->addFieldToFilter('seller_id', $sellerId);
        return count($collection);
    }

    public function getTotalCustomers()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )->getTotalCustomersCount();
        return count($collection);
    }

    public function getTotalCustomersCurrentMonth()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )->addFieldToFilter(
            'created_at',
            [
                'datetime' => true,
                'from' => date('Y-m').'-01 00:00:00',
                'to' => date('Y-m').'-31 23:59:59',
            ]
        )->getTotalCustomersCount();
        return count($collection);
    }

    public function getTotalCustomersLastMonth()
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )->addFieldToFilter(
            'created_at',
            [
                'datetime' => true,
                'from' => date('Y-m', strtotime('last month')).'-01 00:00:00',
                'to' => date('Y-m', strtotime('last month')).'-31 23:59:59',
            ]
        )->getTotalCustomersCount();
        return count($collection);
    }
}
