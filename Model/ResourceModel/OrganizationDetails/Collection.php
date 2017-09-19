<?php
 
namespace Havasay\Havasay\Model\ResourceModel\OrganizationDetails;
 
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
 
class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Havasay\Havasay\Model\OrganizationDetails',
            'Havasay\Havasay\Model\ResourceModel\OrganizationDetails'
        );
    }
}
