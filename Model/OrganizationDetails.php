<?php
 
namespace Havasay\Havasay\Model;
 
use Magento\Framework\Model\AbstractModel;
 
class OrganizationDetails extends AbstractModel
{
    protected function _construct()
    {
        $this->_init('Havasay\Havasay\Model\ResourceModel\OrganizationDetails');
    }
    
    public function setOrganizationDetails($data)
    {
        $this->setData('org_key', $data->orgKey);
        $this->setData('org_id', $data->orgId);
        $this->setData('channel_id', $data->channelId);
        $this->setData('org_secret', $data->orgSecret);
        $this->setData('org_name', $data->orgName);
        $this->setData('store_id', $data->storeId);
        $this->setData('havasay_path', $data->havasayPath);
        $this->setData('list_name', $data->listName);
    }
}
