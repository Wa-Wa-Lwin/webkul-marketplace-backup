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

interface SellerFlagReasonRepositoryInterface
{
    /**
     * Save SellerFlagReason
     * @param \Webkul\Marketplace\Api\Data\SellerFlagReasonInterface $sellerFlagReason
     * @return \Webkul\Marketplace\Api\Data\SellerFlagReasonInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Webkul\Marketplace\Api\Data\SellerFlagReasonInterface $sellerFlagReason
    );

    /**
     * Retrieve SellerFlagReason
     * @param int $entityId
     * @return \Webkul\Marketplace\Api\Data\SellerFlagReasonInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($entityId);

    /**
     * Retrieve SellerFlagReason matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Webkul\Marketplace\Api\Data\SellerFlagReasonSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete SellerFlagReason
     * @param \Webkul\Marketplace\Api\Data\SellerFlagReasonInterface $sellerFlagReason
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Webkul\Marketplace\Api\Data\SellerFlagReasonInterface $sellerFlagReason
    );

    /**
     * Delete SellerFlagReason by ID
     * @param string $entityId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($entityId);
}
