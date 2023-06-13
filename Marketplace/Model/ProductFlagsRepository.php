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
use Webkul\Marketplace\Model\ResourceModel\ProductFlags as ResourceProductFlags;
use Magento\Framework\Exception\CouldNotSaveException;
use Webkul\Marketplace\Api\Data\ProductFlagsSearchResultsInterfaceFactory;
use Webkul\Marketplace\Api\Data\ProductFlagsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Api\DataObjectHelper;
use Webkul\Marketplace\Api\ProductFlagsRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Webkul\Marketplace\Model\ResourceModel\ProductFlags\CollectionFactory as ProductFlagsCollectionFactory;

class ProductFlagsRepository implements ProductFlagsRepositoryInterface
{

    /**
     * @var ResourceProductFlags
     */
    protected $resource;

    /**
     * @var ProductFlagsCollectionFactory
     */
    protected $productFlagsCollectionFactory;

    /**
     * @var ProductFlagsInterfaceFactory
     */
    protected $dataProductFlagsFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var ProductFlagsFactory
     */
    protected $productFlagsFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductFlagsSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @param ResourceProductFlags $resource
     * @param ProductFlagsFactory $productFlagsFactory
     * @param ProductFlagsInterfaceFactory $dataProductFlagsFactory
     * @param ProductFlagsCollectionFactory $productFlagsCollectionFactory
     * @param ProductFlagsSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceProductFlags $resource,
        ProductFlagsFactory $productFlagsFactory,
        ProductFlagsInterfaceFactory $dataProductFlagsFactory,
        ProductFlagsCollectionFactory $productFlagsCollectionFactory,
        ProductFlagsSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->productFlagsFactory = $productFlagsFactory;
        $this->productFlagsCollectionFactory = $productFlagsCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataProductFlagsFactory = $dataProductFlagsFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Webkul\Marketplace\Api\Data\ProductFlagsInterface $productFlags
    ) {
        try {
            $this->resource->save($productFlags);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the ProductFlag: %1',
                $exception->getMessage()
            ));
        }
        return $productFlags;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($productFlagId)
    {
        $productFlags = $this->productFlagsFactory->create();
        $this->resource->load($productFlags, $productFlagId);
        if (!$productFlags->getId()) {
            throw new NoSuchEntityException(
                __('ProductFlag with id "%1" does not exist.', $productFlagId)
            );
        }
        return $productFlags;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->productFlagsCollectionFactory->create();
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
        \Webkul\Marketplace\Api\Data\ProductFlagsInterface $productFlags
    ) {
        try {
            $this->resource->delete($productFlags);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the ProductFlag: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($productFlagId)
    {
        return $this->delete($this->getById($productFlagId));
    }
}
