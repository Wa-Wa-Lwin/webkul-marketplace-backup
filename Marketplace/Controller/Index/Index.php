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
namespace Webkul\Marketplace\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Webkul\Marketplace\Helper\Data as HelperData;

/**
 * Webkul Marketplace Landing page Index Controller.
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     * @param HelperData  $helper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        HelperData $helper
    ) {
        $this->_resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Marketplace Landing page.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $marketplacelabel = $this->helper->getMarketplaceHeadLabel();
        if (!$marketplacelabel) {
            $marketplacelabel = 'Marketplace Landing Page';
        }
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__($marketplacelabel));

        return $resultPage;
    }
}
