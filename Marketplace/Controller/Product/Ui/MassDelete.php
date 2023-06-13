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

namespace Webkul\Marketplace\Controller\Product\Ui;

use Magento\Framework\App\Action\Action;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory as SellerProduct;
use Webkul\Marketplace\Helper\Data as HelperData;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Url as CustomerUrl;

/**
 * Webkul Marketplace Product MassDelete controller.
 */
class MassDelete extends Action
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
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var SellerProduct
     */
    protected $_sellerProductCollectionFactory;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CustomerUrl
     */
    private $customerUrl;

    /**
     * @param Context           $context
     * @param Filter            $filter
     * @param Session           $customerSession
     * @param Registry          $coreRegistry
     * @param CollectionFactory $productCollectionFactory
     * @param SellerProduct     $sellerProductCollectionFactory
     * @param HelperData        $helper
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerUrl       $customerUrl
     */
    public function __construct(
        Context $context,
        Filter $filter,
        Session $customerSession,
        Registry $coreRegistry,
        CollectionFactory $productCollectionFactory,
        SellerProduct $sellerProductCollectionFactory,
        HelperData $helper,
        ProductRepositoryInterface $productRepository = null,
        CustomerUrl $customerUrl
    ) {
        $this->filter = $filter;
        $this->_customerSession = $customerSession;
        $this->_coreRegistry = $coreRegistry;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_sellerProductCollectionFactory = $sellerProductCollectionFactory;
        $this->helper = $helper;
        $this->productRepository = $productRepository
            ?: \Magento\Framework\App\ObjectManager::getInstance()->create(ProductRepositoryInterface::class);
        parent::__construct(
            $context
        );
        $this->customerUrl = $customerUrl;
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
        $assignIds = [];
        if ($isPartner == 1) {
            try {
                $registry = $this->_coreRegistry;
                if (!$registry->registry('mp_flat_catalog_flag')) {
                    $registry->register('mp_flat_catalog_flag', 1);
                }
                $collection = $this->filter->getCollection(
                    $this->_productCollectionFactory->create()
                );

                $ids = $collection->getAllIds();
                $wholedata = [];

                $sellerId = $this->helper->getCustomerId();
                $this->_coreRegistry->register('isSecureArea', 1);
                $deletedIdsArr = [];
                $sellerProducts = $this->_sellerProductCollectionFactory
                ->create()
                ->addFieldToFilter(
                    'mageproduct_id',
                    ['in' => $ids]
                )->addFieldToFilter(
                    'seller_id',
                    $sellerId
                );
                foreach ($sellerProducts as $sellerProduct) {
                    array_push($deletedIdsArr, $sellerProduct['mageproduct_id']);
                    $wholedata['id'] = $sellerProduct['mageproduct_id'];
                    $this->_eventManager->dispatch(
                        'mp_delete_product',
                        [$wholedata]
                    );
                    if ($this->_customerSession->getAssignProductIds()) {
                        $assignIds = $this->_customerSession->getAssignProductIds();
                    }
                    if (!in_array($sellerProduct['mageproduct_id'], $assignIds)) {
                        $sellerProduct->delete();
                    }
                }

                foreach ($deletedIdsArr as $id) {
                    try {
                        if (!in_array($id, $assignIds)) {
                            $product = $this->productRepository->getById($id);
                            $this->productRepository->delete($product);
                        }
                    } catch (\Exception $e) {
                        $this->helper->logDataInLogger(
                            "Controller_Product_Ui_MassDelete execute : ".$e->getMessage()
                        );
                        $this->messageManager->addError($e->getMessage());
                    }
                }

                $unauthIds = array_diff($ids, $deletedIdsArr);
                $this->_coreRegistry->unregister('isSecureArea');
                if (!count($unauthIds)) {
                    // clear cache
                    $this->helper->clearCache();
                    $this->messageManager->addSuccess(
                        __('A total of %1 record(s) have been deleted.', count($deletedIdsArr))
                    );
                }
            } catch (\Exception $e) {
                $this->helper->logDataInLogger(
                    "Controller_Product_Ui_MassDelete execute : ".$e->getMessage()
                );
                $this->messageManager->addError($e->getMessage());
            }
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/product/productlist',
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
