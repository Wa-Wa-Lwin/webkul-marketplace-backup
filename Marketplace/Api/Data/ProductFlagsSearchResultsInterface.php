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

interface ProductFlagsSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get ProductFlags list.
     * @return \Webkul\Marketplace\Api\Data\ProductFlagsInterface[]
     */
    public function getItems();

    /**
     * Set ProductFlags list.
     * @param \Webkul\Marketplace\Api\Data\ProductFlagsInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
