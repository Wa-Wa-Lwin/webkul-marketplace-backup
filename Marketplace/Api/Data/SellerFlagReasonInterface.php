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

interface SellerFlagReasonInterface
{
    const ENTITY_ID  = 'entity_id';
    const REASON     = 'reason';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const STATUS     = 'status';

    /**
     * Gets the entity ID.
     *
     * @return int Entity ID.
     */
    public function getEntityId();

    /**
     * Sets entity ID.
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId);

    /**
     * Gets the Reason.
     *
     * @return string Reason
     */
    public function getReason();

    /**
     * Sets the Reason.
     *
     * @param string $reason
     * @return $this
     */
    public function setReason($reason);

    /**
     * Gets creation timestamp.
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Sets creation timestamp.
     *
     * @param string $timestamp
     * @return $this
     */
    public function setCreatedAt($timestamp);

    /**
     * Gets last update timestamp.
     *
     * @return string
     */
    public function getUpdatedAt();

    /**
     * Sets last update timestamp.
     *
     * @param string $timestamp
     * @return $this
     */
    public function setUpdatedAt($timestamp);

    /**
     * Gets the reason status.
     *
     * @return int
     */
    public function getStatus();

    /**
     * Sets the reason status.
     *
     * @param int $status
     * @return $this
     */
    public function setStatus($status);
}
