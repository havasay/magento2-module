<?php
 
namespace Havasay\Havasay\Controller\Index;
 
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
 
class OrganizationController extends Action
{

    protected $_modelOrganizationDetailsFactory;
    protected $_importProcess;
    protected $_logger;
  
    public function __construct(
        Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Havasay\Havasay\Model\OrganizationDetailsFactory $modelOrganizationDetailsFactory,
        \Havasay\Havasay\Block\ReviewsWithDataPushBlock $importProcess,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_request = $request;
        $this->_modelOrganizationDetailsFactory = $modelOrganizationDetailsFactory;
        $this->_importProcess = $importProcess;
        $this->_logger = $logger;
        parent::__construct($context);
    }
 
    public function execute()
    {
        $PostDataArr = $this->_request->getPost()->toArray();
        
        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body);

        $organizationDetailsModel = $this->_modelOrganizationDetailsFactory->create();
        
        $organizationDetailsModel->setOrganizationDetails($data);
        
        $organizationDetailsModel->save();
        
        //print_r($organizationDetailsModel);
         $obj = ['store_id' => $data->storeId,
                'org_id' => $data->orgId,
                'org_key' => $data->orgKey,
                'channel_id' => $data->channelId,
                'org_secret' => $data->orgSecret,
                'list_name' => $data->listName,
                'havasay_path' => $data->havasayPath];
                 
                $this->_importProcess->execute($obj);
         $this->_logger->info("created root category for store ");
    }
}
