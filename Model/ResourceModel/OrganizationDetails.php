<?php
 
namespace Havasay\Havasay\Model\ResourceModel;
 
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
 
class OrganizationDetails extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('havasay_organization_details', 'id');
    }
    
    public function loadByOrgname($orgName)
    {
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("org_name = ?", $orgName);
        $sql = $this->getConnection()->select()->from($table, ['id'])->where($where);
        $id = $this->getConnection()->fetchOne($sql);
        return $id;
    }
    
    public function loadByStoreId($storeId)
    {
        $table = $this->getMainTable();
        $where = $this->getConnection()->quoteInto("store_id = ?", $storeId);
        $sql = $this->getConnection()->select()->from($table, ['id'])->where($where);
        $id = $this->getConnection()->fetchOne($sql);
        return $id;
    }
}
