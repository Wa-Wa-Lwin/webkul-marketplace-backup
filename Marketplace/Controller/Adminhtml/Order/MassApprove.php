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

namespace Webkul\Marketplace\Controller\Adminhtml\Order;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Model\OrderPendingMailsFactory;
use Webkul\Marketplace\Helper\Email as MpEmailHelper;
use MIT\Customer\Helper\SMSSender;

/**
 * Class MassApprove used to mass mass approved.
 */
class MassApprove extends \Magento\Backend\App\Action
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var MpHelper
     */
    protected $mpHelper;

    /**
     * @var OrderPendingMailsFactory
     */
    protected $orderPendingMails;

    /**
     * @var MpEmailHelper
     */
    protected $mpEmailHelper;

    /**
     * @var SMSSender
     */
    protected $smsSender;

    /**
     * @param Context           $context
     * @param Filter            $filter
     * @param CollectionFactory $collectionFactory
     * @param MpHelper          $mpHelper
     * @param OrderPendingMailsFactory $orderPendingMails
     * @param MpEmailHelper     $mpEmailHelper
     * @param SMSSender         $smsSender
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        MpHelper $mpHelper,
        OrderPendingMailsFactory $orderPendingMails,
        MpEmailHelper $mpEmailHelper,
        SMSSender $smsSender
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->mpHelper = $mpHelper;
        $this->orderPendingMails = $orderPendingMails;
        $this->mpEmailHelper = $mpEmailHelper;
        parent::__construct($context);
        $this->smsSender = $smsSender;
    }

    /**
     * Execute action.
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     *
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());

        $status = 1;

        $helper = $this->mpHelper;

        foreach ($collection as $item) {
            $orderPendingMailsCollection = $this->orderPendingMails->create()
            ->getCollection()
            ->addFieldToFilter('status', 0)
            ->addFieldToFilter(
                'order_id',
                $item->getId()
            );
            foreach ($orderPendingMailsCollection as $key => $value) {
                $emailTemplateVariables = [];
                $emailTempVariables['myvar1'] = $value['myvar1'];
                $emailTempVariables['myvar2'] = $value['myvar2'];
                $emailTempVariables['myvar3'] = $value['myvar3'];
                $emailTempVariables['myvar4'] = $value['myvar4'];
                $emailTempVariables['myvar5'] = $value['myvar5'];
                $emailTempVariables['myvar6'] = $value['myvar6'];
                $emailTempVariables['myvar8'] = $value['myvar8'];
                $emailTempVariables['myvar9'] = $value['myvar9'];
                $emailTempVariables['isNotVirtual'] = $value['isNotVirtual'];

                $senderInfo = [];
                $senderInfo['name'] = $value['sender_name'];
                $senderInfo['email'] = $value['sender_email'];

                $receiverInfo = [];
                $receiverInfo['name'] = $value['receiver_name'];
                $receiverInfo['email'] = $value['receiver_email'];
                $value->setStatus(1)->save();
                $item->setOrderApprovalStatus(1)->save();

                $sellerEmail = $receiverInfo['email']; 

                if (preg_match('/^\+?\d+$/', $sellerEmail))
                {

                    $message = ("A record(s) for order has been approved.");
                        
                    $this->smsSender->sendSMS(
                        $sellerEmail,
                        $message
                    );
                    $this->messageManager->addSuccess(__('A record for order has been approved. This information is notified via phone number.'));
                }
                else
                {
                    $this->mpEmailHelper->sendPlacedOrderEmail(
                        $emailTempVariables,
                        $senderInfo,
                        $receiverInfo
                    );
                }
            }

            $this->_eventManager->dispatch(
                'mp_approve_order',
                ['order' => $item]
            );
        }
        $this->messageManager->addSuccess(
            __(
                'A total of %1 record(s) have been approved.',
                $collection->getSize()
            )
        );

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('sales/order/');
    }

    /**
     * Check for is allowed.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Webkul_Marketplace::seller');
    }
}
