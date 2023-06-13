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

namespace Webkul\Marketplace\Controller\Seller;

use Magento\Customer\Controller\AccountInterface;
use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Webkul\Marketplace\Helper\Data as HelperData;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Webkul\Marketplace\Model\FeedbackcountFactory;
use Webkul\Marketplace\Model\FeedbackFactory;

/**
 * Webkul Marketplace Seller Newfeedback controller.
 */
class Newfeedback extends Action implements AccountInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var FeedbackcountFactory
     */
    protected $feedbackcountModel;

    /**
     * @var FeedbackFactory
     */
    protected $feedbackFactory;

    /**
     * @param Context                                     $context
     * @param Session                                     $customerSession
     * @param FormKeyValidator                            $formKeyValidator
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param HelperData $helper
     * @param FeedbackcountFactory $feedbackcountModel
     * @param FeedbackFactory      $feedbackFactory
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        HelperData $helper,
        FeedbackcountFactory $feedbackcountModel,
        FeedbackFactory $feedbackFactory
    ) {
        $this->_customerSession = $customerSession;
        $this->_formKeyValidator = $formKeyValidator;
        $this->_date = $date;
        $this->helper = $helper;
        $this->feedbackcountModel = $feedbackcountModel;
        $this->feedbackFactory = $feedbackFactory;
        parent::__construct(
            $context
        );
    }

    /**
     * Retrieve customer session object.
     *
     * @return \Magento\Customer\Model\Session
     */
    protected function _getSession()
    {
        return $this->_customerSession;
    }

    /**
     * Save New Seller feeback entry in table.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $helper = $this->helper;
        if (!$helper->getSellerProfileDisplayFlag()) {
            $this->getRequest()->initForward();
            $this->getRequest()->setActionName('noroute');
            $this->getRequest()->setDispatched(false);

            return false;
        }
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $wholedata = $this->getRequest()->getParams();

        if ($this->getRequest()->isPost()) {
            try {
                if (!$this->_formKeyValidator->validate($this->getRequest())) {
                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/feedback',
                        ['shop' => $wholedata['shop_url']]
                    );
                }
                $sellerId = $wholedata['seller_id'];
                $buyerId = $this->_getSession()->getCustomerId();
                $buyerEmail = $this->_getSession()->getEmail();
                $wholedata['buyer_id'] = $buyerId;
                $wholedata['buyer_email'] = $buyerEmail;
                $wholedata['created_at'] = $this->_date->gmtDate();
                $wholedata['admin_notification'] = 1;
                $feedbackcount = 0;
                $collectionfeed = $this->feedbackcountModel->create()
                ->getCollection()
                ->addFieldToFilter(
                    'seller_id',
                    $sellerId
                )->addFieldToFilter(
                    'buyer_id',
                    [$buyerId]
                );
                foreach ($collectionfeed as $value) {
                    $feedbackcount = $value->getFeedbackCount();
                    $value->setFeedbackCount($feedbackcount + 1);
                    $value->save();
                }
                $collection = $this->feedbackFactory->create();
                $collection->setData($wholedata);
                $collection->save();

                $this->messageManager->addSuccess(
                    __('Your Review was successfully saved')
                );

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/feedback',
                    ['shop' => $wholedata['shop_url']]
                );
            } catch (\Exception $e) {
                $this->helper->logDataInLogger(
                    "controller_Seller_Newfeedback execute : ".$e->getMessage()
                );
                $this->messageManager->addError($e->getMessage());

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/feedback',
                    ['shop' => $wholedata['shop_url']]
                );
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                '*/*/feedback',
                ['shop' => $wholedata['shop_url']]
            );
        }
    }
}
