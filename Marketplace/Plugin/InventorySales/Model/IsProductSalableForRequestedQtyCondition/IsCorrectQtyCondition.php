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
namespace Webkul\Marketplace\Plugin\InventorySales\Model\IsProductSalableForRequestedQtyCondition;

use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterfaceFactory;
use Magento\Framework\Phrase;
use Webkul\Marketplace\Helper\Data as MarketplaceHelperData;
use Magento\Catalog\Api\ProductRepositoryInterface;

class IsCorrectQtyCondition
{
    /**
     * @var ProductSalabilityErrorInterfaceFactory
     */
    protected $productSalabilityErrorFactory;

    /**
     * @var ProductSalableResultInterfaceFactory
     */
    protected $productSalableResultFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var MarketplaceHelperData
     */
    protected $marketplaceHelperData;

    /**
     * @param ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory
     * @param ProductSalableResultInterfaceFactory $productSalableResultFactory
     * @param MarketplaceHelperData               $marketplaceHelperData
     * @param ProductRepositoryInterface           $productRepository
     */
    public function __construct(
        ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory,
        ProductSalableResultInterfaceFactory $productSalableResultFactory,
        ProductRepositoryInterface $productRepository,
        MarketplaceHelperData $marketplaceHelperData
    ) {
        $this->productSalabilityErrorFactory = $productSalabilityErrorFactory;
        $this->productSalableResultFactory = $productSalableResultFactory;
        $this->productRepository = $productRepository;
        $this->marketplaceHelperData = $marketplaceHelperData;
    }

    /**
     * {@inheritdoc}
     */
    public function aroundExecute(
        \Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition\IsCorrectQtyCondition $subject,
        callable $proceed,
        string $sku,
        int $stockId,
        float $requestedQty
    ) {
        $mpProductCartLimit = $this->checkAndUpdateProductCartLimit($sku);
        if ($mpProductCartLimit && $mpProductCartLimit != "" && $mpProductCartLimit < $requestedQty) {
            return $this->createErrorResult(
                'is_correct_qty-max_sale_qty',
                __('The requested qty exceeds the maximum %1 qty allowed in shopping cart', $mpProductCartLimit)
            );
        }
        return $proceed($sku, $stockId, $requestedQty);
    }

    /**
     * Create Error Result Object
     *
     * @param string $code
     * @param Phrase $message
     * @return ProductSalableResultInterface
     */
    public function createErrorResult(string $code, Phrase $message): ProductSalableResultInterface
    {
        $errors = [
            $this->productSalabilityErrorFactory->create([
                'code' => $code,
                'message' => $message
            ])
        ];
        return $this->productSalableResultFactory->create(['errors' => $errors]);
    }

    /**
     * [checkAndUpdateProductCartLimit is used to check cart items limit]
     * @param  string $sku
     * @return bool|float
     */
    public function checkAndUpdateProductCartLimit(string $sku)
    {
        try {
            $allowProductLimit = $this->marketplaceHelperData->getAllowProductLimit();
            if ($allowProductLimit) {
                $product = $this->productRepository->get($sku);
                $sellerProductDataColl = $this->marketplaceHelperData->getSellerProductDataByProductId(
                    $product->getId()
                );
                if (count($sellerProductDataColl)) {
                    $productTypeId = $product['type_id'];
                    if ($productTypeId != 'downloadable' && $productTypeId != 'virtual') {
                        $mpProductCartLimit = $product['mp_product_cart_limit'];
                        if (!$mpProductCartLimit) {
                            $mpProductCartLimit = $this->marketplaceHelperData->getGlobalProductLimitQty();
                        }
                        return $mpProductCartLimit;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->marketplaceHelperData->logDataInLogger(
                "Plugin_IsCorrectQtyCondition checkAndUpdateProductCartLimit : ".$e->getMessage()
            );
        }
        return false;
    }
}
