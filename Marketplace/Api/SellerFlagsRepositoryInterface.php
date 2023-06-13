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

interface SellerFlagsRepositoryInterface
{
    /**
     * Save SellerFlags
     * @param \Webkul\Marketplace\Api\Data\SellerFlagsInterface $sellerFlags
     * @return \Webkul\Marketplace\Api\Data\SellerFlagsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Webkul\Marketplace\Api\Data\SellerFlagsInterface $sellerFlags
    );

    /**
     * Retrieve SellerFlags
     * @param int $entityId
     * @return \Webkul\Marketplace\Api\Data\SellerFlagsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($entityId);

    /**
     * Retrieve SellerFlags matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Webkul\Marketplace\Api\Data\SellerFlagsSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete SellerFlags
     * @param \Webkul\Marketplace\Api\Data\SellerFlagsInterface $sellerFlags
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Webkul\Marketplace\Api\Data\SellerFlagsInterface $sellerFlags
    );

    /**
     * Delete SellerFlags by ID
     * @param string $entityId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($entityId);
}
