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

namespace Webkul\Marketplace\Model;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Exception\NoSuchEntityException;
use Webkul\Marketplace\Model\ResourceModel\SellerFlagReason as ResourceSellerFlagReason;
use Magento\Framework\Exception\CouldNotSaveException;
use Webkul\Marketplace\Api\Data\SellerFlagReasonSearchResultsInterfaceFactory;
use Webkul\Marketplace\Api\Data\SellerFlagReasonInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Api\DataObjectHelper;
use Webkul\Marketplace\Api\SellerFlagReasonRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Webkul\Marketplace\Model\ResourceModel\SellerFlagReason\CollectionFactory as SellerFlagReasonCollectionFactory;

class SellerFlagReasonRepository implements SellerFlagReasonRepositoryInterface
{

    /**
     * @var ResourceSellerFlagReason
     */
    protected $resource;

    /**
     * @var SellerFlagReasonCollectionFactory
     */
    protected $sellerFlagReasonCollectionFactory;

    /**
     * @var SellerFlagReasonInterfaceFactory
     */
    protected $dataSellerFlagReasonFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var SellerFlagReasonFactory
     */
    protected $sellerFlagReasonFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var SellerFlagReasonSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @param ResourceSellerFlagReason $resource
     * @param SellerFlagReasonFactory $sellerFlagReasonFactory
     * @param SellerFlagReasonInterfaceFactory $dataSellerFlagReasonFactory
     * @param SellerFlagReasonCollectionFactory $sellerFlagReasonCollectionFactory
     * @param SellerFlagReasonSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceSellerFlagReason $resource,
        SellerFlagReasonFactory $sellerFlagReasonFactory,
        SellerFlagReasonInterfaceFactory $dataSellerFlagReasonFactory,
        SellerFlagReasonCollectionFactory $sellerFlagReasonCollectionFactory,
        SellerFlagReasonSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->sellerFlagReasonFactory = $sellerFlagReasonFactory;
        $this->sellerFlagReasonCollectionFactory = $sellerFlagReasonCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataSellerFlagReasonFactory = $dataSellerFlagReasonFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Webkul\Marketplace\Api\Data\SellerFlagReasonInterface $sellerFlagReason
    ) {
        try {
            $this->resource->save($sellerFlagReason);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the sellerFlagReason: %1',
                $exception->getMessage()
            ));
        }
        return $sellerFlagReason;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($sellerFlagReasonId)
    {
        $sellerFlagReason = $this->sellerFlagReasonFactory->create();
        $this->resource->load($sellerFlagReason, $sellerFlagReasonId);
        if (!$sellerFlagReason->getId()) {
            throw new NoSuchEntityException(__('Seller Flag Reason with id "%1" does not exist.', $sellerFlagReasonId));
        }
        return $sellerFlagReason;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->sellerFlagReasonCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $fields = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() === 'store_id') {
                    $collection->addStoreFilter($filter->getValue(), false);
                    continue;
                }
                $fields[] = $filter->getField();
                $condition = $filter->getConditionType() ?: 'eq';
                $conditions[] = [$condition => $filter->getValue()];
            }
            $collection->addFieldToFilter($fields, $conditions);
        }

        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \Webkul\Marketplace\Api\Data\SellerFlagReasonInterface $sellerFlagReason
    ) {
        try {
            $this->resource->delete($sellerFlagReason);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the SellerFlagReason: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($sellerFlagReasonId)
    {
        return $this->delete($this->getById($sellerFlagReasonId));
    }
}
