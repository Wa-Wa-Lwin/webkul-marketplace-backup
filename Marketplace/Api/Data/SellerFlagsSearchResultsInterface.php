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

namespace Webkul\Marketplace\Api\Data;

interface SellerFlagsSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get SellerFlags list.
     * @return \Webkul\Marketplace\Api\Data\SellerFlagsInterface[]
     */
    public function getItems();

    /**
     * Set SellerFlags list.
     * @param \Webkul\Marketplace\Api\Data\SellerFlagsInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
