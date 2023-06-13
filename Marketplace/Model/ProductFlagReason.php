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

use Webkul\Marketplace\Api\Data\ProductFlagReasonInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;

class ProductFlagReason extends \Magento\Framework\Model\AbstractModel implements ProductFlagReasonInterface
{
    /**
     * Marketplace ProductFlag cache tag.
     */
    const CACHE_TAG = 'marketplace_productflagreason';

    /**
     * @var string
     */
    protected $_cacheTag = 'marketplace_productflagreason';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'marketplace_productflagreason';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Webkul\Marketplace\Model\ResourceModel\ProductFlagReason::class);
    }

    /**
     * Get entity_id
     * @return string
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Webkul\Marketplace\Api\Data\ProductFlagReasonInterface
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get reason
     * @return string
     */
    public function getReason()
    {
        return $this->getData(self::REASON);
    }

    /**
     * Set reason
     * @param string $reason
     * @return \Webkul\Marketplace\Api\Data\ProductFlagReasonInterface
     */
    public function setReason($reason)
    {
        return $this->setData(self::REASON, $reason);
    }

    /**
     * Get CreatedAt
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set CreatedAt
     * @param string $timestamp
     * @return \Webkul\Marketplace\Api\Data\ProductFlagReasonInterface
     */
    public function setCreatedAt($timestamp)
    {
        return $this->setData(self::CREATED_AT, $timestamp);
    }

    /**
     * Get UpdatedAt
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set UpdatedAt
     * @param string $timestamp
     * @return \Webkul\Marketplace\Api\Data\ProductFlagReasonInterface
     */
    public function setUpdatedAt($timestamp)
    {
        return $this->setData(self::UPDATED_AT, $timestamp);
    }

    /**
     * Get status
     * @return int
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Set status
     * @param int $status
     * @return \Webkul\Marketplace\Api\Data\ProductFlagReasonInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }
}
