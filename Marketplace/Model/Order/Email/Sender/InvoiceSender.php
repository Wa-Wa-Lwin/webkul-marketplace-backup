<?php
declare(strict_types=1);

namespace Webkul\Marketplace\Model\Order\Email\Sender;

use Magento\Sales\Model\Order\Invoice;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order;

class InvoiceSender extends \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
{
    /**
     * {inheriate}
     */
    public function send(Invoice $invoice, $forceSyncMode = false)
    {
        $this->identityContainer->setStore($invoice->getStore());
        $invoice->setSendEmail($this->identityContainer->isEnabled());

        if (!$this->globalConfig->getValue('sales_email/general/async_sending') || $forceSyncMode) {
            $order = $invoice->getOrder();
            if ($this->checkIfPartialInvoice($order, $invoice)) {
                $order->setBaseSubtotal((float) $invoice->getBaseSubtotal());
                $order->setBaseTaxAmount((float) $invoice->getBaseTaxAmount());
                //$order->setBaseShippingAmount((float) $invoice->getBaseShippingAmount());
            }

            $transport = [
                'order' => $order,
                'order_id' => $order->getId(),
                'invoice' => $invoice,
                'invoice_id' => $invoice->getId(),
                'comment' => $invoice->getCustomerNoteNotify() ? $invoice->getCustomerNote() : '',
                'billing' => $order->getBillingAddress(),
                'payment_html' => $this->getPaymentHtml($order),
                'store' => $order->getStore(),
                'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
                'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
                'order_data' => [
                    'customer_name' => $order->getCustomerName(),
                    'is_not_virtual' => $order->getIsNotVirtual(),
                    'email_customer_note' => $order->getEmailCustomerNote(),
                    'frontend_status_label' => $order->getFrontendStatusLabel()
                ]
            ];
            $transportObject = new DataObject($transport);

            /**
             * Event argument `transport` is @deprecated. Use `transportObject` instead.
             */
            $this->eventManager->dispatch(
                'email_invoice_set_template_vars_before',
                ['sender' => $this, 'transport' => $transportObject->getData(), 'transportObject' => $transportObject]
            );

            $this->templateContainer->setTemplateVars($transportObject->getData());

            if ($this->checkAndSend($order)) {
                $invoice->setEmailSent(true);
                $this->invoiceResource->saveAttribute($invoice, ['send_email', 'email_sent']);
                return true;
            }
        } else {
            $invoice->setEmailSent(null);
            $this->invoiceResource->saveAttribute($invoice, 'email_sent');
        }

        $this->invoiceResource->saveAttribute($invoice, 'send_email');

        return false;
    }

    /**
     * Check if the order contains partial invoice
     *
     * @param Order $order
     * @param Invoice $invoice
     * @return bool
     */
    private function checkIfPartialInvoice(Order $order, Invoice $invoice): bool
    {
        $totalQtyOrdered = (float) $order->getTotalQtyOrdered();
        $totalQtyInvoiced = (float) $invoice->getTotalQty();
        return $totalQtyOrdered !== $totalQtyInvoiced;
    }
}
