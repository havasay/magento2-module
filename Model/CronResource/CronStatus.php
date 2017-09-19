<?php

namespace Havasay\Havasay\Model\CronResource;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CronStatus extends AbstractDb
{

    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('havasay_cron_status', 'id');
    }
}
