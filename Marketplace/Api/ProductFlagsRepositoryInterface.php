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

namespace Webkul\Marketplace\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface ProductFlagsRepositoryInterface
{
    /**
     * Save ProductFlag
     * @param \Webkul\Marketplace\Api\Data\ProductFlagsInterface $productFlags
     * @return \Webkul\Marketplace\Api\Data\ProductFlagsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Webkul\Marketplace\Api\Data\ProductFlagsInterface $productFlags
    );

    /**
     * Retrieve ProductFlag
     * @param int $entityId
     * @return \Webkul\Marketplace\Api\Data\ProductFlagsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($entityId);

    /**
     * Retrieve ProductFlag matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Webkul\Marketplace\Api\Data\ProductFlagsSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete ProductFlag
     * @param \Webkul\Marketplace\Api\Data\ProductFlagsInterface $productFlags
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Webkul\Marketplace\Api\Data\ProductFlagsInterface $productFlags
    );

    /**
     * Delete ProductFlag by ID
     * @param string $entityId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($entityId);
}
