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

interface ProductFlagReasonSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get ProductFlag list.
     * @return \Webkul\Marketplace\Api\Data\ProductFlagReasonInterface[]
     */
    public function getItems();

    /**
     * Set ProductFlag list.
     * @param \Webkul\Marketplace\Api\Data\ProductFlagReasonInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
