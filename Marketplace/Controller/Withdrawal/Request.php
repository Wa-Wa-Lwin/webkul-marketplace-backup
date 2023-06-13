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

namespace Webkul\Marketplace\Controller\Withdrawal;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory as SaleslistColl;
use Webkul\Marketplace\Model\ResourceModel\Saleperpartner\CollectionFactory;
use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Helper\Email as HelperEmail;
use Magento\Customer\Api\CustomerRepositoryInterface;
use MIT\Customer\Helper\SMSSender;

/**
 * Webkul Marketplace Withdrawal Request Controller.
 */
class Request extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var HelperEmail
     */
    protected $helperEmail;

    /**
     * @var SaleslistColl
     */
    protected $saleslistColl;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var SMSSender
     */
    protected $smsSender;



    /**
     * @param Context                     $context
     * @param FormKeyValidator            $formKeyValidator
     * @param HelperData                  $helper
     * @param HelperEmail                 $helperEmail
     * @param SaleslistColl               $saleslistColl
     * @param CollectionFactory           $collectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param SMSSender                   $smsSender
     */
    public function __construct(
        Context $context,
        FormKeyValidator $formKeyValidator,
        HelperData $helper,
        HelperEmail $helperEmail,
        SaleslistColl $saleslistColl,
        CollectionFactory $collectionFactory,
        CustomerRepositoryInterface $customerRepository,
        SMSSender $smsSender
    ) {
        $this->_formKeyValidator = $formKeyValidator;
        $this->helper = $helper;
        $this->helperEmail = $helperEmail;
        $this->saleslistColl = $saleslistColl;
        $this->collectionFactory = $collectionFactory;
        $this->customerRepository = $customerRepository;
        $this->smsSender = $smsSender;
        parent::__construct(
            $context
        );
    }

    /**
     * seller product save action.
     *
     * @return \Magento\Framework\Controller\Result\RedirectFactory
     */
    public function execute()
    {
        $helper = $this->helper;
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            try {
                if ($this->getRequest()->isPost()) {
                    if (!$this->_formKeyValidator->validate($this->getRequest())) {
                        return $this->resultRedirectFactory->create()->setPath(
                            'marketplace/transaction/history',
                            ['_secure' => $this->getRequest()->isSecure()]
                        );
                    }
                    $paramData = $this->getRequest()->getParams();
                    if (empty($paramData['is_requested']) || $paramData['is_requested'] != '1') {
                        return $this->resultRedirectFactory->create()->setPath(
                            'marketplace/transaction/history',
                            ['_secure' => $this->getRequest()->isSecure()]
                        );
                    }
                    $sellerId = $helper->getCustomerId();
                    $collection = $this->saleslistColl->create();

                    $coditionArr = [];
                    $condition = "`seller_id`=".$sellerId;
                    array_push($coditionArr, $condition);
                    $condition = "`cpprostatus`=1";
                    array_push($coditionArr, $condition);
                    $condition = "`paid_status`=0";
                    array_push($coditionArr, $condition);
                    $coditionData = implode(' AND ', $coditionArr);

                    $collection->setWithdrawalRequestData(
                        $coditionData,
                        ['is_withdrawal_requested' => 1]
                    );

                    $adminStoreEmail = $helper->getAdminEmailId();
                    $adminEmail = $adminStoreEmail ? $adminStoreEmail : $helper->getDefaultTransEmailId();
                    $adminUsername = $helper->getAdminName();

                    $seller = $this->customerRepository->getById(
                        $sellerId
                    );

                    $emailTemplateVariables = [];
                    $emailTemplateVariables['seller'] = $seller->getFirstName();
                    $emailTemplateVariables['amount'] = $helper->getFormatedPrice(
                        $this->getRemainTotal()
                    );

                    $receiverInfo = [
                        'name' => $adminUsername,
                        'email' => $adminEmail,
                    ];
                    $senderInfo = [
                        'name' => $seller->getFirstName(),
                        'email' => $seller->getEmail(),
                    ];

                    $sellerEmail = $senderInfo['email'];
                    $message = "Request to withdraw is receieved.";

                    if (preg_match('/^\+?\d+$/', $sellerEmail)){
                        $this->smsSender->sendSMS(
                            $sellerEmail,
                            $message
                        );
                     
                        $this->messageManager->addSuccess(
                            __('Your withdrawal request has been sent successfully via your phone number.')
                        );
                        
                        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/withdraw.log');
                        $logger = new \Zend_Log();
                        $logger->addWriter($writer);
                        $logger->info('---------');
                        $logger->info($message);
                        $logger->info('---------');
                    }
                    else
                    {

                        $this->helperEmail->sendWithdrawalRequestMail(
                            $emailTemplateVariables,
                            $senderInfo,
                            $receiverInfo
                        );
                        $this->messageManager->addSuccess(
                            __('Your withdrawal request has been sent successfully via your mail.')
                        );

                    }


                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $helper->logDataInLogger(
                    "Controller_Withdrawal_Request execute : ".$e->getMessage()
                );
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $helper->logDataInLogger(
                    "Controller_Withdrawal_Request execute : ".$e->getMessage()
                );
                $this->messageManager->addError($e->getMessage());
            }
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/transaction/history',
                [
                    '_secure' => $this->getRequest()->isSecure(),
                ]
            );
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }

    /**
     * @return int|float
     */
    public function getRemainTotal()
    {
        $sellerId = $this->helper->getCustomerId();
        $collection = $this->collectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        );
        $total = 0;
        foreach ($collection->getTotalAmountRemain() as $data) {
            $total = $data['amount_remain'];
        }
        return $total;
    }
}
