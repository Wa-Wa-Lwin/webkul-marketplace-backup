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

namespace Webkul\Marketplace\Controller\Adminhtml\Seller;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Webkul\Marketplace\Model\SellerFactory;

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
     * @var SellerFactory
     */
    protected $sellerModel;

    /**
     * @param Context       $context
     * @param PageFactory   $resultPageFactory
     * @param SellerFactory $sellerModel
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        SellerFactory $sellerModel
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->sellerModel = $sellerModel;
    }

    /**
     * Seller list page.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $sellerCollection = $this->sellerModel->create()
        ->getCollection()
        ->addFieldToFilter('admin_notification', ['neq' => 0]);
        if ($sellerCollection->getSize()) {
            $this->_updateNotification($sellerCollection);
        }
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Webkul_Marketplace::seller');
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Sellers'));
        return $resultPage;
    }

    protected function _updateNotification($collection)
    {
        foreach ($collection as $value) {
            $value->setAdminNotification(0);
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
        return $this->_authorization->isAllowed('Webkul_Marketplace::seller');
    }
}
