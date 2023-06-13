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

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\App\RequestInterface;
use Webkul\Marketplace\Helper\Data as HelperData;
use Magento\Customer\Model\Url as CustomerUrl;
use Webkul\Marketplace\Model\SaleperpartnerFactory as MpSalesPartner;

/**
 * Webkul Marketplace Account Save Minimum Order Informartion Controller.
 */
class SaveMinOrder extends \Magento\Customer\Controller\AbstractAccount
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
     * @var CustomerUrl
     */
    protected $customerUrl;

    /**
     * @var MpSalesPartner
     */
    protected $mpSalesPartner;

    /**
     * @param Context                                    $context
     * @param Session                                    $customerSession
     * @param FormKeyValidator                           $formKeyValidator
     * @param Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param HelperData                                 $helper
     * @param CustomerUrl                                $customerUrl
     * @param MpSalesPartner                             $mpSalesPartner
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        HelperData $helper,
        CustomerUrl $customerUrl,
        MpSalesPartner $mpSalesPartner
    ) {
        $this->_customerSession = $customerSession;
        $this->_formKeyValidator = $formKeyValidator;
        $this->_date = $date;
        $this->helper = $helper;
        $this->customerUrl = $customerUrl;
        $this->mpSalesPartner = $mpSalesPartner;
        parent::__construct(
            $context
        );
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
     * Seller's SavePaymentInfo action.
     *
     * @return \Magento\Framework\Controller\Result\RedirectFactory
     */
    public function execute()
    {
        if ($this->getRequest()->isPost()) {
            try {
                if (!$this->_formKeyValidator->validate($this->getRequest())) {
                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/editProfile',
                        ['_secure' => $this->getRequest()->isSecure()]
                    );
                }
                $fields = $this->getRequest()->getParams();
                $sellerId = $this->helper->getCustomerId();
                $salePerPartnerColl = $this->mpSalesPartner->create()
                                    ->getCollection()
                                    ->addFieldToFilter(
                                        'seller_id',
                                        $sellerId
                                    );
                if ($salePerPartnerColl->getSize() == 1) {
                    foreach ($salePerPartnerColl as $verifyrow) {
                        $verifyrow->setMinOrderAmount($fields['min_order_amount']);
                        $verifyrow->setMinOrderStatus(1);
                        $verifyrow->save();
                    }
                } else {
                    $collectioninsert = $this->mpSalesPartner->create();
                    $collectioninsert->setSellerId($sellerId);
                    $collectioninsert->setMinOrderAmount($fields['min_order_amount']);
                    $collectioninsert->setMinOrderStatus(1);
                    $collectioninsert->save();
                }
                $this->messageManager->addSuccess(
                    __('Minimum order amount information was successfully saved')
                );
            } catch (\Exception $e) {
                $this->helper->logDataInLogger(
                    "Controller_Account_SaveMinOrder execute : ".$e->getMessage()
                );
                $this->messageManager->addError($e->getMessage());
            }
        }
        return $this->resultRedirectFactory->create()->setPath(
            '*/*/editProfile',
            ['_secure' => $this->getRequest()->isSecure()]
        );
    }
}
