<?php

namespace Havasay\Havasay\Model\CronResource;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CronEntityStatus extends AbstractDb
{

    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('havasay_cron_entity_status', 'id');
    }
}
