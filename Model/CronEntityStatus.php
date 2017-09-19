<?php

namespace Havasay\Havasay\Model;

use Magento\Framework\Model\AbstractModel;

class CronEntityStatus extends AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{

    const CACHE_TAG = 'havasay_cronjob_cronentitystatusItem';

    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Havasay\Havasay\Model\CronResource\CronEntityStatus');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
