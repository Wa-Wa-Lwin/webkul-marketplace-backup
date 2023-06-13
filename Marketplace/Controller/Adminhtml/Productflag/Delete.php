<?php
/**
 * Webkul Software
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\Marketplace\Controller\Adminhtml\Productflag;

use Webkul\Marketplace\Api\Data\ProductFlagReasonInterfaceFactory;

class Delete extends \Webkul\Marketplace\Controller\Adminhtml\Productflag
{

    /**
     * @var ProductFlagReasonInterfaceFactory
     */
    protected $productFlagFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param ProductFlagReasonInterfaceFactory $productFlagFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        ProductFlagReasonInterfaceFactory $productFlagFactory
    ) {
        $this->productFlagFactory = $productFlagFactory;
        parent::__construct($context);
    }

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('entity_id');
        if ($id) {
            try {
                // init model and delete
                $model = $this->productFlagFactory->create();
                $model->load($id);
                $model->delete();
                // display success message
                $this->messageManager->addSuccessMessage(__('You deleted the Product flag reason.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['entity_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a Product flag reason to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
