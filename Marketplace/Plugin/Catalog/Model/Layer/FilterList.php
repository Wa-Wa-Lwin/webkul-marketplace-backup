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
namespace Webkul\Marketplace\Plugin\Catalog\Model\Layer;

class FilterList
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $_mpHelper;

    /**
     * Initialize dependencies
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Webkul\Marketplace\Helper\Data $mpHelper
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Webkul\Marketplace\Helper\Data $mpHelper,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->_objectManager = $objectManager;
        $this->_mpHelper = $mpHelper;
        $this->request = $request;
    }

    /**
     * aroundGetFilters Plugin
     *
     * @param \Magento\Catalog\Model\Layer\FilterList $subject
     * @param \Closure $proceed
     * @param \Magento\Catalog\Model\Layer $layer
     * @return array
     */
    public function aroundGetFilters(
        \Magento\Catalog\Model\Layer\FilterList $subject,
        \Closure $proceed,
        \Magento\Catalog\Model\Layer $layer
    ) {
        $result = $proceed($layer);
        if ($this->_mpHelper->allowSellerFilter() &&
            $this->request->getFullActionName() != 'marketplace_seller_collection'
        ) {
            $result[] = $this->_objectManager->create(
                \Webkul\Marketplace\Model\Layer\Filter\Seller::class,
                ['layer' => $layer]
            );
        }

        return $result;
    }
}
