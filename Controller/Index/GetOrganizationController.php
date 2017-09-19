<?php
 
namespace Havasay\Havasay\Controller\Index;
 
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
 
class GetOrganizationController extends Action
{

    protected $_modelOrganizationDetailsFactory;
    
    protected $resultJsonFactory;
    
    public function __construct(
        Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Havasay\Havasay\Model\OrganizationDetailsFactory $modelOrganizationDetailsFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->_request = $request;
        $this->_modelOrganizationDetailsFactory = $modelOrganizationDetailsFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }
 
    public function execute()
    {
        
        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body);
        
        $resultJson = $this->resultJsonFactory->create();
        
        $test = $this->_modelOrganizationDetailsFactory->create();
        //$id = $test->getResource()->loadByOrgname($data->orgName);
        $id = $test->getResource()->loadByStoreId($data->storeId);
        if ($id) {
            $test->load($id);
            return $resultJson->setData(['success' => $test->getData()]);
        } else {
            return $resultJson->setData(['success' => $id]);
        }
    }
}
