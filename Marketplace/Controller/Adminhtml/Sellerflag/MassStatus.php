<?php
/**
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\Marketplace\Controller\Adminhtml\Sellerflag;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Webkul\Marketplace\Model\ResourceModel\SellerFlagReason\CollectionFactory;
use Magento\Framework\App\ResourceConnection;

/**
 * Class MassStatus used to update status.
 */
class MassStatus extends \Magento\Backend\App\Action
{
    /**
     * TABLE_NAME table name
     */
    const TABLE_NAME = 'marketplace_sellerflag_reason';
    /**
     * ENABLE_STATUS Enable Status Value
     */
    const ENABLE_STATUS  = 1;
    /**
     * DISABLE_STATUS Disable Status Value
     */
    const DISABLE_STATUS = 0;
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @param Context $context
     * @param Filter  $filter
     */
    public function __construct(
        Context $context,
        Filter $filter,
        ResourceConnection $resource,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->connection = $resource->getConnection();
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
        $this->_date = $date;
        parent::__construct($context);
    }
    /**
     * Execute action.
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     *
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $countRecord = $collection->getSize();
        $ids = [];
        foreach ($collection as $item) {
            $ids[] = $item->getEntityId();
        }
        $status = $params['status'] ? self::ENABLE_STATUS : self::DISABLE_STATUS;
        $update = ['status' => $status, 'updated_at' => $this->_date->gmtDate()];
        $where = ['entity_id IN (?)' => $ids];
        if (!empty($ids)) {
            try {
                $this->connection->beginTransaction();
                $this->connection->update($this->resource->getTableName(self::TABLE_NAME), $update, $where);
                $this->connection->commit();
            } catch (\Exception $e) {
                $this->connection->rollBack();
            }
        }
        $this->messageManager->addSuccess(
            __(
                'A total of %1 record(s) have been updated.',
                $countRecord
            )
        );

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/index');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Webkul_Marketplace::sellerflag');
    }
}
