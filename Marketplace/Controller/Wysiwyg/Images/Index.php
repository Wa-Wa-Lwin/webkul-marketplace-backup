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
namespace Webkul\Marketplace\Controller\Wysiwyg\Images;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\LayoutFactory;

class Index extends \Webkul\Marketplace\Controller\Wysiwyg\Images
{
    /**
     * @var \Magento\Framework\View\Result\LayoutFactory
     */
    protected $resultLayoutFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        LayoutFactory $resultLayoutFactory
    ) {
        $this->resultLayoutFactory = $resultLayoutFactory;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * Wysuwyg Images Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $currentStoreId = (int)$this->getRequest()->getParam('store');

        try {
            $this->_objectManager->get(\Magento\Cms\Helper\Wysiwyg\Images::class)->getCurrentPath();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        $this->_initAction();
        /** @var \Magento\Framework\View\Result\Layout $resultLayout */
        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->addHandle('overlay_popup');
        $block = $resultLayout->getLayout()->getBlock('wysiwyg_images.js');
        if ($block) {
            $block->setStoreId($currentStoreId);
        }
        return $resultLayout;
    }
}
