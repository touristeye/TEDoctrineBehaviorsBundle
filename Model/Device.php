<?php

namespace TE\DoctrineBehaviorsBundle\Model;

/**
 * Device trait.
 *
 * Should be used inside entity where you need to track
 * which device created or updated it
 */
trait Device
{
    /**
     * Will be mapped to an integer
     * by DeviceListener
     */
    private $createdOn;

    /**
     * Will be mapped to an integer
     * by DeviceListener
     */
    private $updatedOn;

    /**
     * @param int $device
     */
    public function setCreatedOn($device)
    {
        $this->createdOn = $device;
    }

    /**
     * @param int $device
     */
    public function setUpdatedOn($device)
    {
        $this->updatedOn = $device;
    }

    /**
     * @return int $device
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * @return int $device
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    public function hasDevice()
    {
        return true;
    }
}
