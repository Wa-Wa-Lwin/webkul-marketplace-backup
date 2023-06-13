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
namespace Webkul\Marketplace\Controller\Adminhtml\Productflag;

use Magento\Framework\Exception\LocalizedException;

class Save extends \Magento\Backend\App\Action
{
    protected $dataPersistor;

    /**
     * @var \Webkul\Marketplace\Model\ProductFlagReasonFactory
     */
    protected $productFlagFactory;

    /**
     * @var \Webkul\Marketplace\Api\ProductFlagReasonRepositoryInterface
     */
    protected $productFlagRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Webkul\Marketplace\Model\ProductFlagReasonFactory $productFlagReasonFactory,
        \Webkul\Marketplace\Api\ProductFlagReasonRepositoryInterface $productFlagReasonRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->productFlagFactory = $productFlagReasonFactory;
        $this->productFlagRepository = $productFlagReasonRepository;
        $this->_date = $date;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $id = $this->getRequest()->getParam('entity_id');

            $model = $this->productFlagFactory->create()->load($id);
            if (!$model->getId() && $id) {
                $this->messageManager->addErrorMessage(__('This flag reason no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }

            $model->setReason($data['reason']);
            $model->setStatus($data['status']);
            if (!$model->getId()) {
                $model->setCreatedAt($this->_date->gmtDate());
            }
            $model->setUpdatedAt($this->_date->gmtDate());
            try {
                $this->productFlagRepository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the product flag reason.'));
                $this->dataPersistor->clear('productflag');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['entity_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e->getMessage());
            }

            $this->dataPersistor->set('productflag', $data);
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $this->getRequest()->getParam('entity_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Check for is allowed.
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Webkul_Marketplace::productflag');
    }
}
