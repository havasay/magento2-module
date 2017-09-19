<?php

namespace Havasay\Havasay\Model\CronResource\CronEntityStatus;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Havasay\Havasay\Model\CronEntityStatus',
            'Havasay\Havasay\Model\CronResource\CronEntityStatus'
        );
    }
}
