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

interface SellerFlagReasonSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get SellerFlag list.
     * @return \Webkul\Marketplace\Api\Data\SellerFlagReasonInterface[]
     */
    public function getItems();

    /**
     * Set SellerFlag list.
     * @param \Webkul\Marketplace\Api\Data\SellerFlagReasonInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
