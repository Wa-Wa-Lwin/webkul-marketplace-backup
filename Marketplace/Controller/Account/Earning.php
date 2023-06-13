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
namespace Webkul\Marketplace\Controller\Account;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\Url as CustomerUrl;

/**
 * Webkul Marketplace Earning Controller.
 */
class Earning extends Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $helperData;

    /**
     * @var CustomerUrl
     */
    protected $customerUrl;

    /**
     * @param Context                         $context
     * @param PageFactory                     $resultPageFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Webkul\Marketplace\Helper\Data $helperData
     * @param CustomerUrl                     $customerUrl
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Webkul\Marketplace\Helper\Data $helperData,
        CustomerUrl $customerUrl
    ) {
        $this->_customerSession = $customerSession;
        $this->_resultPageFactory = $resultPageFactory;
        $this->helperData = $helperData;
        $this->customerUrl = $customerUrl;
        parent::__construct($context);
    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->customerUrl->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    /**
     * Seller's Earning Page.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $helper = $this->helperData;
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            $params = $this->getRequest()->getParams();
            $this->_customerSession->setEarningParams(json_encode($params));
            /** @var \Magento\Framework\View\Result\Page $resultPage */
            $resultPage = $this->_resultPageFactory->create();
            if ($helper->getIsSeparatePanel()) {
                $resultPage->addHandle('marketplace_layout2_account_earning');
            }
            $resultPage->getConfig()->getTitle()->set(
                __('Earnings')
            );
            return $resultPage;
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
