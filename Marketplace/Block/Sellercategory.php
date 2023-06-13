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

use Magento\Catalog\Model\Category;
use Webkul\Marketplace\Helper\Data as MpHelper;

class Sellercategory extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Catalog\Model\Category
     */
    protected $category;

    /**
     * @var \Webkul\Marketplace\Helper\Data $helper
     */
    protected $helper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param Category $category
     * @param \Webkul\Marketplace\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Category $category,
        MpHelper $helper,
        array $data = []
    ) {
        $this->category = $category;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Get Category By Id
     *
     * @param int $id
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function getCategoryById($id)
    {
        return $this->category->load($id);
    }

    /**
     * Get Seller Profile Details
     *
     * @return \Webkul\Marketplace\Model\Seller | bool
     */
    public function getProfileDetail()
    {
        return $this->helper->getProfileDetail(MpHelper::URL_TYPE_COLLECTION);
    }
}
