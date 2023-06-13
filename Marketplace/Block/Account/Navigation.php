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

use Webkul\Marketplace\Model\ProductFactory;
use Webkul\Marketplace\Model\OrdersFactory;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;
use Webkul\Marketplace\Model\SellertransactionFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;

/**
 * Marketplace Navigation link
 *
 */
class Navigation extends \Magento\Framework\View\Element\Html\Link
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $product;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    /**
     * @var int
     */
    protected $orderId;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var OrdersFactory
     */
    protected $ordersFactory;

    /**
     * @var CollectionFactory
     */
    protected $productCollection;

    /**
     * @var SellertransactionFactory
     */
    protected $sellertransaction;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productModel;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderModel;

    /**
     * @var \Webkul\Marketplace\Model\SaleslistFactory
     */
    protected $saleslistModel;

    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $shipconfig;

    /**
     * @var \Magento\Payment\Model\Config
     */
    protected $paymentConfig;

    /**
     * @var MpHelper
     */
    protected $mpHelper;
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Customer\Model\Session $customerSession
     * @param ProductFactory $productFactory
     * @param OrdersFactory $ordersFactory
     * @param CollectionFactory $productCollection
     * @param SellertransactionFactory $sellertransaction
     * @param \Magento\Catalog\Model\ProductFactory $productModel
     * @param \Magento\Sales\Model\OrderFactory $orderModel
     * @param \Webkul\Marketplace\Model\SaleslistFactory $saleslistModel
     * @param \Magento\Shipping\Model\Config $shipconfig
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param MpHelper $mpHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Customer\Model\Session $customerSession,
        ProductFactory $productFactory,
        OrdersFactory $ordersFactory,
        CollectionFactory $productCollection,
        SellertransactionFactory $sellertransaction,
        \Magento\Catalog\Model\ProductFactory $productModel,
        \Magento\Sales\Model\OrderFactory $orderModel,
        \Webkul\Marketplace\Model\SaleslistFactory $saleslistModel,
        \Magento\Shipping\Model\Config $shipconfig,
        \Magento\Payment\Model\Config $paymentConfig,
        MpHelper $mpHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->date = $date;
        $this->customerSession = $customerSession;
        $this->productFactory = $productFactory;
        $this->ordersFactory = $ordersFactory;
        $this->productCollection = $productCollection;
        $this->sellertransaction = $sellertransaction;
        $this->productModel = $productModel;
        $this->orderModel = $orderModel;
        $this->saleslistModel = $saleslistModel;
        $this->shipconfig = $shipconfig;
        $this->paymentConfig = $paymentConfig;
        $this->mpHelper = $mpHelper;
    }
    /**
     * [getCurrentUrl Give the current url of recently viewed page]
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->_urlBuilder->getCurrentUrl();
    }
    /**
     * getMpHelper give the object of Marketplace helper data class
     * @return MpHelper
     */
    public function getMpHelper()
    {
        return $this->mpHelper;
    }

    /**
     * Get all marketplce product collection seller wise.
     * @return \Webkul\Marketplace\Model\Product
     */
    public function getProductCollection()
    {
        $sellerId = $this->mpHelper->getCustomerId();
        $mpProductsCollection = $this->productFactory->create()
                            ->getCollection()
                            ->addFieldToFilter(
                                'seller_id',
                                $sellerId
                            )->addFieldToFilter(
                                'seller_pending_notification',
                                1
                            );
        return $mpProductsCollection;
    }
    /**
     * Get all marketplce product collection seller wise.
     * @return \Webkul\Marketplace\Model\Product
     */
    public function getMarketplaceOrderCollection()
    {
        $sellerId = $this->mpHelper->getCustomerId();
        $mpOrderCollection = $this->ordersFactory->create()
                            ->getCollection()
                            ->addFieldToFilter(
                                'seller_id',
                                $sellerId
                            )->addFieldToFilter(
                                'seller_pending_notification',
                                1
                            );
        $salesOrder = $this->productCollection->create()->getTable('sales_order');

        $mpOrderCollection->getSelect()->join(
            $salesOrder.' as so',
            'main_table.order_id = so.entity_id'
        )->where(
            'so.order_approval_status = 1'
        );
        return $mpOrderCollection;
    }
    /**
     * Get all transaction for seller.
     * @return \Webkul\Marketplace\Model\Product
     */
    public function getTransactionCollection()
    {
        $sellerId = $this->mpHelper->getCustomerId();
        $transactionCollection = $this->sellertransaction->create()
                                ->getCollection()
                                ->addFieldToFilter(
                                    'seller_id',
                                    $sellerId
                                )->addFieldToFilter(
                                    'seller_pending_notification',
                                    1
                                )->setOrder('created_at', 'DESC');
        return $transactionCollection;
    }
    /**
     * Load product by id
     * @param  int $productId
     * @return \Magento\Catalog\Model\Product
     */
    public function loadProduct($productId)
    {
        $product = $this->productModel->create()->load($productId);
        return $product;
    }
    /**
     * Load order by id
     * @param  int $productId
     * @return \Magento\Catalog\Model\Product
     */
    public function loadOrder($orderId)
    {
        if ($this->orderId == $orderId) {
            return $this->order;
        }
        $order = $this->orderModel->create()->load($orderId);
        $this->orderId = $orderId;
        $this->order = $order;
        return $this->order;
    }

    /**
     * Count total notifications.
     * @return int
     */
    public function getProductNotificationCount()
    {
        return $this->getProductCollection()->getSize();
    }

    /**
     * Generate notification title according to product status.
     * @param  int $productId
     * @param  int $productStatus
     * @return string
     */
    public function getProductNotificationTitle($productId, $productStatus)
    {
        $product = $this->loadProduct($productId);
        if ($productStatus == 1) {
            return __('Product approved');
        } else {
            return __('Product disapproved');
        }
    }

    /**
     * Generate notification body according to product status.
     * @param  int $productId
     * @param  int $productStatus
     * @return string
     */
    public function getProductNotificationDesc($productId, $productStatus)
    {
        $product = $this->loadProduct($productId);
        if ($productStatus == 1) {
            return __(
                sprintf(
                    'Product %s has been approved by admin.
                    Please go to your My Product List section to check product(s) status',
                    '<span class="wk-focus">'.$product->getName().'</span>'
                )
            );
        } else {
            return __(
                sprintf(
                    'Product %s has been disapproved by admin.
                    Please go to your My Product List section to check product(s) status',
                    '<span class="wk-focus">'.$product->getName().'</span>'
                )
            );
        }
    }

    public function getProductNotifyDateTime($date)
    {
        return $this->date->gmtDate('l jS \of F Y h:i:s A', strtotime($date));
    }

    /**
     * Count total order notifications.
     * @return int
     */
    public function getOrderNotificationCount()
    {
        return $this->getMarketplaceOrderCollection()->getSize();
    }

    /**
     * Generate notification title according to order status.
     * @param  int $productId
     * @param  int $productStatus
     * @return string
     */
    public function getOrderNotificationTitle($orderId)
    {
        $order = $this->loadOrder($orderId);
        return __('Order placed notification');
    }

    /**
     * Generate notification body according to order.
     * @param  int $productId
     * @param  int $productStatus
     * @return string
     */
    public function getOrderNotificationDesc($orderId)
    {
        $sellerId = $this->mpHelper->getCustomerId();
        $order = $this->loadOrder($orderId);
        $saleslistIds = [];
        $collection1 = $this->saleslistModel->create()
                      ->getCollection()
                      ->addFieldToFilter('order_id', $orderId)
                      ->addFieldToFilter('seller_id', $sellerId)
                      ->addFieldToFilter('parent_item_id', ['null' => 'true'])
                      ->addFieldToFilter('magerealorder_id', ['neq' => 0])
                      ->addFieldToSelect('entity_id');

        $saleslistIds = $collection1->getData();

        $fetchsale = $this->saleslistModel->create()
                    ->getCollection()
                    ->addFieldToFilter(
                        'entity_id',
                        ['in' => $saleslistIds]
                    );
        $fetchsale->getSellerOrderCollection();
        $productNames = [];
        foreach ($fetchsale as $value) {
            $productNames[] = $value->getMageproName();
        }
        $productNames = implode(',', $productNames);
        return __(
            sprintf(
                'Product(s) %s has been sold from your store with order id %s',
                '<span class="wk-focus">'.$productNames.'</span>',
                '<span class="wk-focus">#'.$order->getIncrementId().'</span>'
            )
        );
    }

    public function getOrderCreatedDate($orderId)
    {
        $createdAt = $this->loadOrder($orderId)->getCreatedAt();
        return $this->date->gmtDate('l jS \of F Y h:i:s A', strtotime($createdAt));
    }

    /**
     * getTransactionNotificationCount used to get the count of the transaction
     * according to seller whose notification is pending.
     * @return int
     */
    public function getTransactionNotificationCount()
    {
        return $this->getTransactionCollection()->getSize();
    }

    /**
     * generate notification title
     * @param  int $transactionId
     * @return string
     */
    public function getTransactionNotifyTitle($transactionId)
    {
        $transactionBlock = $this->getLayout()->createBlock(
            \Webkul\Marketplace\Block\Transaction\View::class
        );
        $details = $transactionBlock->sellertransactionOrderDetails($transactionId);
        $orderId = $details->getFirstItem()->getMagerealorderId();
        $title = __(sprintf('Payment has been successfully done for "#%s" Order', $orderId));
        return $title;
    }

    /**
     * generate notification description
     * @param  int $transactionId
     * @return string
     */
    public function getTransactionNotifyDesc($id)
    {
        $transactionBlock = $this->getLayout()->createBlock(
            \Webkul\Marketplace\Block\Transaction\View::class
        );
        $sellerTransation = $this->sellertransactionDetails($id);
        $details = $transactionBlock->sellertransactionOrderDetails($id);
        $orderId = $details->getFirstItem()->getMagerealorderId();
        $desc = __(sprintf(
            'You have recieved payment for %s order. Mode of payment is %s.',
            '<span class="wk-focus">#'.$orderId.'</span>',
            '<span class="wk-focus">'.$sellerTransation->getMethod().'</span>'
        ));
        return $desc;
    }
    /**
     * [sellertransactionDetails is used to get the Seller Transaction table data by Id]
     * @param  int $id
     * @return \Webkul\Marketplace\Model\Sellertransaction
     */
    public function sellertransactionDetails($id)
    {
        return $this->sellertransaction->create()->load($id);
    }
    /**
     * [getTransactionDate is used to convert into gmt date format]
     * @param  string $date
     * @return string
     */
    public function getTransactionDate($date)
    {
        return $this->date->gmtDate('l jS \of F Y h:i:s A', strtotime($date));
    }
}
