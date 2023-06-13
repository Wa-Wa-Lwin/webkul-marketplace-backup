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
use Webkul\Marketplace\Model\ResourceModel\ProductFlagReason as ResourceProductFlag;
use Magento\Framework\Exception\CouldNotSaveException;
use Webkul\Marketplace\Api\Data\ProductFlagReasonSearchResultsInterfaceFactory;
use Webkul\Marketplace\Api\Data\ProductFlagReasonInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Api\DataObjectHelper;
use Webkul\Marketplace\Api\ProductFlagReasonRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Webkul\Marketplace\Model\ResourceModel\ProductFlagReason\CollectionFactory as ProductFlagCollectionFactory;

class ProductFlagReasonRepository implements ProductFlagReasonRepositoryInterface
{

    /**
     * @var ResourceProductFlag
     */
    protected $resource;

    /**
     * @var ProductFlagCollectionFactory
     */
    protected $productFlagCollectionFactory;

    /**
     * @var ProductFlagReasonInterfaceFactory
     */
    protected $dataProductFlagFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var ProductFlagReasonFactory
     */
    protected $productFlagFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductFlagReasonSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @param ResourceProductFlag $resource
     * @param ProductFlagReasonFactory $productFlagFactory
     * @param ProductFlagReasonInterfaceFactory $dataProductFlagFactory
     * @param ProductFlagCollectionFactory $productFlagCollectionFactory
     * @param ProductFlagReasonSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceProductFlag $resource,
        ProductFlagReasonFactory $productFlagFactory,
        ProductFlagReasonInterfaceFactory $dataProductFlagFactory,
        ProductFlagCollectionFactory $productFlagCollectionFactory,
        ProductFlagReasonSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->productFlagFactory = $productFlagFactory;
        $this->productFlagCollectionFactory = $productFlagCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataProductFlagFactory = $dataProductFlagFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Webkul\Marketplace\Api\Data\ProductFlagReasonInterface $productFlagReason
    ) {
        try {
            $this->resource->save($productFlagReason);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the Product Flag Reason: %1',
                $exception->getMessage()
            ));
        }
        return $productFlagReason;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($productFlagReasonId)
    {
        $productFlagReason = $this->productFlagFactory->create();
        $this->resource->load($productFlagReason, $productFlagReasonId);
        if (!$productFlagReason->getId()) {
            throw new NoSuchEntityException(
                __('Product Flag Reason with id "%1" does not exist.', $productFlagReasonId)
            );
        }
        return $productFlagReason;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->productFlagCollectionFactory->create();
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
        \Webkul\Marketplace\Api\Data\ProductFlagReasonInterface $productFlagReason
    ) {
        try {
            $this->resource->delete($productFlagReason);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the ProductFlag Reason: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($productFlagReasonId)
    {
        return $this->delete($this->getById($productFlagReasonId));
    }
}
