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

namespace Webkul\Marketplace\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Webkul\Marketplace\Model\ProductFactory;

class Index extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\View\Result\Page
     */
    protected $resultPage;

    /**
     * @var ProductFactory
     */
    protected $productModel;

    /**
     * @param Context        $context
     * @param PageFactory    $resultPageFactory
     * @param ProductFactory $productModel
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ProductFactory $productModel
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->productModel = $productModel;
    }

    /**
     * Product list page.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $productCollection = $this->productModel->create()
        ->getCollection()
        ->addFieldToFilter('admin_pending_notification', ['neq' => 0]);
        if ($productCollection->getSize()) {
            $this->_updateNotification($productCollection);
        }
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Webkul_Marketplace::product');
        $resultPage->getConfig()->getTitle()->prepend(__("Manage Seller's Product"));
        return $resultPage;
    }

    /**
     * Updated all notification as read.
     * @param   \Webkul\Marketplace\Model\Product $collection
     */
    protected function _updateNotification($collection)
    {
        foreach ($collection as $value) {
            $value->setAdminPendingNotification(0);
            $value->setId($value->getEntityId())->save();
        }
    }

    /**
     * Check for is allowed.
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Webkul_Marketplace::product');
    }
}
