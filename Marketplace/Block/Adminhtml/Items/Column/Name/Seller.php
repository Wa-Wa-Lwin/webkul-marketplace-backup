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

namespace Webkul\Marketplace\Block\Adminhtml\Items\Column\Name;

class Seller extends \Magento\Sales\Block\Adminhtml\Items\Column\Name
{
    /**
     * @var \Webkul\Marketplace\Model\SaleslistFactory
     */
    protected $saleslistFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerModel;
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\Product\OptionFactory $optionFactory
     * @param \Webkul\Marketplace\Model\SaleslistFactory $saleslistFactory
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Magento\Customer\Model\CustomerFactory $customerModel
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Product\OptionFactory $optionFactory,
        \Webkul\Marketplace\Model\SaleslistFactory $saleslistFactory,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Customer\Model\CustomerFactory $customerModel,
        array $data = []
    ) {
        $this->saleslistFactory = $saleslistFactory;
        $this->urlInterface = $urlInterface;
        $this->customerModel = $customerModel;
        parent::__construct($context, $stockRegistry, $stockConfiguration, $registry, $optionFactory, $data);
    }

    /**
     * Get Seller Name.
     *
     * @param string | $id
     *
     * @return array
     */
    public function getUserInfo($id)
    {
        $sellerId = 0;
        $order = $this->getOrder();
        $orderId = $order->getId();
        $marketplaceSalesCollection = $this->saleslistFactory->create()
        ->getCollection()
        ->addFieldToFilter(
            'mageproduct_id',
            ['eq' => $id]
        )
        ->addFieldToFilter(
            'order_id',
            ['eq' => $orderId]
        );
        if (count($marketplaceSalesCollection)) {
            foreach ($marketplaceSalesCollection as $mpSales) {
                $sellerId = $mpSales->getSellerId();
            }
        }
        if ($sellerId > 0) {
            $customer = $this->customerModel->create()->load($sellerId);
            if ($customer) {
                $returnArray = [];
                $returnArray['name'] = $customer->getName();
                $returnArray['id'] = $sellerId;

                return $returnArray;
            }
        }
    }

    /**
     * Get Customer Url By Customer Id.
     *
     * @param string | $customerId
     *
     * @return string
     */
    public function getCustomerUrl($customerId)
    {
        $urlbuilder = $this->urlInterface;
        return $urlbuilder->getUrl(
            'customer/index/edit',
            ['id' => $customerId]
        );
    }
}
