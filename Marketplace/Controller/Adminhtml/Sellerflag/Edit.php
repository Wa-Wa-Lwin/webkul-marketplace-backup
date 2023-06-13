<?php

/**
 * Webkul Software
 *
 * @category Webkul
 * @package Webkul_Marketplace
 * @author Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license https://store.webkul.com/license.html
 */
namespace Webkul\Marketplace\Controller\Adminhtml\Sellerflag;

use Webkul\Marketplace\Api\Data\SellerFlagReasonInterfaceFactory;

class Edit extends \Webkul\Marketplace\Controller\Adminhtml\Sellerflag
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var SellerFlagReasonInterfaceFactory
     */
    protected $sellerFlagFactory;

    /**
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param SellerFlagReasonInterfaceFactory $sellerFlagFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        SellerFlagReasonInterfaceFactory $sellerFlagFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->sellerFlagFactory = $sellerFlagFactory;
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Edit action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('entity_id');
        $model = $this->sellerFlagFactory->create();
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This SellerFlag Reason no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $this->coreRegistry->register('sellerflag_reason', $model);
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Reason') : __('New Reason'),
            $id ? __('Edit Reason') : __('New Reason')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Reason'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? $model->getTitle() : __('New Reason'));
        return $resultPage;
    }

    /**
     * Init page
     *
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     */
    public function initPage($resultPage)
    {
        $resultPage->setActiveMenu('Webkul_Marketplace::sellerflag');
        return $resultPage;
    }
}
