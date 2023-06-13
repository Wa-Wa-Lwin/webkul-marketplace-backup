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

namespace Webkul\Marketplace\Block\Adminhtml\Customer\Edit\Tab;

use Magento\Customer\Controller\RegistryConstants;

class Product extends \Magento\Backend\Block\Template
{
    /**
     * Block template.
     *
     * @var string
     */
    protected $_template = 'seller/assign_products.phtml';

    /**
     * @var \Webkul\Marketplace\Block\Adminhtml\Customer\Edit\Tab\Grid\Product
     */
    protected $blockGrid;

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Product\Collection
     */
    protected $_sellerProduct;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @param \Magento\Backend\Block\Template\Context                    $context
     * @param \Magento\Framework\Registry                                $coreRegistry
     * @param \Webkul\Marketplace\Model\ResourceModel\Product\Collection $sellerProduct
     * @param \Magento\Framework\Json\EncoderInterface                   $jsonEncoder
     * @param \Webkul\Marketplace\Block\Adminhtml\Customer\Edit          $customerEdit
     * @param array                                                      $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Webkul\Marketplace\Model\ResourceModel\Product\Collection $sellerProduct,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_sellerProduct = $sellerProduct;
        $this->jsonEncoder = $jsonEncoder;
        $this->customerEdit = $customerEdit;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve instance of grid block.
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBlockGrid()
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                \Webkul\Marketplace\Block\Adminhtml\Customer\Edit\Tab\Grid\Product::class,
                'seller.product.grid'
            );
        }

        return $this->blockGrid;
    }

    /**
     * Return HTML of grid block.
     *
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getBlockGrid()->toHtml();
    }

    /**
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * @return array
     */
    protected function getSellerAssignedProducts()
    {
        $products = $this->_sellerProduct->getAllAssignProducts(
            '`seller_id`='.$this->getCustomerId()
        );

        return $products;
    }

    /**
     * @return string
     */
    public function getSellerAssignedProductsJson()
    {
        $products = $this->getSellerAssignedProducts();
        if (!empty($products)) {
            return $this->jsonEncoder->encode(array_flip($products));
        }

        return '{}';
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        $coll = $this->customerEdit->getMarketplaceUserCollection();
        $isSeller = false;
        foreach ($coll as $row) {
            $isSeller = $row->getIsSeller();
        }
        if ($this->getCustomerId() && $isSeller) {
            return true;
        }

        return false;
    }

    /**
     * Return Tab label.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Product Assignment');
    }

    /**
     * Return Tab Title.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Product Assignment');
    }

    /**
     * Tab class getter.
     *
     * @return string
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * Return URL link to Tab content.
     *
     * @return string
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * Tab should be loaded trough Ajax call.
     *
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * Tab is hidden.
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
}
