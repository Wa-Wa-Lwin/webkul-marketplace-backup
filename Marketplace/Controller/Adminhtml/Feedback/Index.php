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
namespace Webkul\Marketplace\Controller\Adminhtml\Feedback;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Webkul\Marketplace\Model\FeedbackFactory;

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
     * @var FeedbackFactory
     */
    protected $feedbackModel;

    /**
     * @param Context       $context
     * @param PageFactory   $resultPageFactory
     * @param FeedbackFactory $feedbackModel
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        FeedbackFactory $feedbackModel
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->feedbackModel = $feedbackModel;
    }

    /**
     * Feedback list page.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $feedbackCollection = $this->feedbackModel->create()
        ->getCollection()
        ->addFieldToFilter('admin_notification', ['neq' => 0]);
        if ($feedbackCollection->getSize()) {
            $this->_updateNotification($feedbackCollection);
        }
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Webkul_Marketplace::feedback');
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Feedback'));
        return $resultPage;
    }

    /**
     * Updated all notification as read.
     * @param   \Webkul\Marketplace\Model\Feedback $collection
     */
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
        return $this->_authorization->isAllowed('Webkul_Marketplace::feedback');
    }
}
