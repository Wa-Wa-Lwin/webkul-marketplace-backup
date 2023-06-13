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

namespace Webkul\Marketplace\Controller\Order\Ui;

use Magento\Framework\App\Action\Action;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Webkul\Marketplace\Helper\Data as HelperData;
use Magento\Customer\Model\Url as CustomerUrl;
use Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory as MpOrdersCollection;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Webkul\Marketplace\Model\Order\Pdf\Invoice as InvoicePdf;

/**
 * Webkul Marketplace Order Printinvoice controller.
 */
class Printinvoice extends Action implements \Magento\Framework\App\CsrfAwareActionInterface
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var CustomerUrl
     */
    private $customerUrl;

    /**
     * @var MpOrdersCollection
     */
    protected $mpOrdersCollection;

    /**
     * @var InvoiceCollection
     */
    protected $invoiceCollection;

    /**
     * @var InvoicePdf
     */
    protected $invoicePdf;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @param Context           $context
     * @param Filter            $filter
     * @param Session           $customerSession
     * @param CollectionFactory $orderCollectionFactory
     * @param HelperData        $helper
     * @param CustomerUrl       $customerUrl
     * @param MpOrdersCollection $mpOrdersCollection
     * @param InvoiceCollection $invoiceCollection
     * @param InvoicePdf $invoicePdf
     * @param DateTime $date
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        Session $customerSession,
        CollectionFactory $orderCollectionFactory,
        HelperData $helper,
        CustomerUrl $customerUrl,
        MpOrdersCollection $mpOrdersCollection,
        InvoiceCollection $invoiceCollection,
        InvoicePdf $invoicePdf,
        DateTime $date,
        FileFactory $fileFactory
    ) {
        $this->filter = $filter;
        $this->_customerSession = $customerSession;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helper = $helper;
        $this->customerUrl = $customerUrl;
        $this->mpOrdersCollection = $mpOrdersCollection;
        $this->invoiceCollection = $invoiceCollection;
        $this->invoicePdf = $invoicePdf;
        $this->fileFactory = $fileFactory;
        $this->date = $date;
        parent::__construct(
            $context
        );
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
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
     * Mass delete seller products action.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $isPartner = $this->helper->isSeller();
        if ($isPartner == 1) {
            try {
                $sellerId = $this->helper->getCustomerId();
                $collection = $this->filter->getCollection(
                    $this->orderCollectionFactory->create()
                );
                $ids = $collection->getAllIds();
                $mpOrdersCollection = $this->mpOrdersCollection->create()
                                            ->addFieldToFilter(
                                                'order_id',
                                                ['in' =>$ids]
                                            )
                                            ->addFieldToFilter(
                                                'seller_id',
                                                $sellerId
                                            )
                                            ->addFieldToSelect(
                                                'invoice_id'
                                            );
                $invoiceIds = $mpOrdersCollection->getData();
                if (!empty($invoiceIds)) {
                    $invoices = $this->invoiceCollection
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter(
                        'entity_id',
                        ['in' => $invoiceIds]
                    )
                    ->load();

                    if (!$invoices->getSize()) {
                        $this->messageManager->addNotice(
                            __('There are no printable documents related to selected order(s).')
                        );
                        return $this->resultRedirectFactory->create()->setPath(
                            'marketplace/order/history',
                            [
                                '_secure' => $this->getRequest()->isSecure(),
                            ]
                        );
                    }
                    $pdf = $this->invoicePdf->getPdf($invoices);
                    $date = $this->date->date('Y-m-d_H-i-s');

                    return $this->fileFactory->create(
                        'invoiceslip'.$date.'.pdf',
                        $pdf->render(),
                        DirectoryList::VAR_DIR,
                        'application/pdf'
                    );
                } else {
                    $this->messageManager->addNotice(
                        __('There are no printable documents related to selected order(s).')
                    );
                }
            } catch (\Exception $e) {
                $this->helper->logDataInLogger(
                    "Controller_Order_Ui_Printinvoice execute : ".$e->getMessage()
                );
                $this->messageManager->addError($e->getMessage());
            }
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/order/history',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
