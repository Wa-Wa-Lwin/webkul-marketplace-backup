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

namespace Webkul\Marketplace\Controller\Seller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Webkul\Marketplace\Helper\Data as HelperData;

/**
 * Marketplace Seller Collection controller.
 */
class Collection extends Action
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
     * @param HelperData $helper
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
     * Marketplace Seller's Product Collection Page.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $helper = $this->helper;
        if (!$helper->getSellerProfileDisplayFlag()) {
            $this->getRequest()->initForward();
            $this->getRequest()->setActionName('noroute');
            $this->getRequest()->setDispatched(false);

            return false;
        }
        $shopUrl = $this->helper->getCollectionUrl();
        if (!$shopUrl) {
            $shopUrl = $this->getRequest()->getParam('shop');
        }
        if ($shopUrl) {
            $data = $helper->getSellerDataByShopUrl($shopUrl);
            if ($data->getSize()) {
                $resultPage = $this->_resultPageFactory->create();
                return $resultPage;
            }
        }

        return $this->resultRedirectFactory->create()->setPath(
            'marketplace',
            ['_secure' => $this->getRequest()->isSecure()]
        );
    }
}
