<?php
 
namespace Havasay\Havasay\Controller\Index;
 
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Havasay\Havasay\Model\OrganizationDetailsFactory;
 
class Index extends Action
{
    /**
     * @var \Havasay\Havasay\Model\OrganizationDetailsFactory
     */
    protected $_modelOrganizationDetailsFactory;
 
    /**
     * @param Context $context
     * @param OrganizationDetailsFactory $modelNewsFactory
     */
    public function __construct(
        Context $context,
        OrganizationDetailsFactory $modelOrganizationDetailsFactory
    ) {
        parent::__construct($context);
        $this->_modelOrganizationDetailsFactory = $modelOrganizationDetailsFactory;
    }
 
    public function execute()
    {
        /**
         * When Magento get your model, it will generate a Factory class
         * for your model at var/generaton folder and we can get your
         * model by this way
         */
        $organizationDetailsModel = $this->_modelOrganizationDetailsFactory->create();
 
        // Load the item with ID is 1
        $item = $organizationDetailsModel->load(1);
        var_dump($item->getData());
 
        // Get news collection
        $organizationDetailsCollection = $organizationDetailsModel->getCollection();
        // Load all data of collection
        var_dump($organizationDetailsCollection->getData());
    }
}
