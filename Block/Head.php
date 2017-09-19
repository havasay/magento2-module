<?php

namespace Havasay\Havasay\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Module\Manager;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class Head extends \Magento\Framework\View\Element\Template
{
    public $assetRepository;
    public $_manager;
    protected $_request;
    protected $_storeConfig;
    protected $_storeManager;
    protected $_organizationDetailsFactory;
    protected $_categoryFactory;
    protected $_logger;

    public function __construct(Context $context, array $data = [], Http $request, Manager $manager, \Havasay\Havasay\Model\OrganizationDetailsFactory $organizationDetailsFactory, \Magento\Framework\Registry $registry, \Magento\Catalog\Model\CategoryFactory $categoryFactory)
    {
        // Get the asset repository to get URL of our assets
        $this->assetRepository = $context->getAssetRepository();
        $this->_request = $request;
        $this->_manager = $manager;
        $this->_storeConfig = $context->getScopeConfig();
        $this->_storeManager = $context->getStoreManager();
        $this->_organizationDetailsFactory = $organizationDetailsFactory;
        $this->_registry = $registry;
        $this->_categoryFactory = $categoryFactory;
        $this->_logger = $context->getLogger();
        return parent::__construct($context, $data);
    }

    public function getFullActionName()
    {
        return $this->_request->getFullActionName();
    }

    public function isProductPage()
    {
        /*
        ** To get product, home and catalog page
        *  http://magento.stackexchange.com/questions/93148/how-to-check-ishomepage-in-magento-2?newreg=4f2bd8ef6f3c4f398ff83ede31a4b12b *
        */
        return $this->getFullActionName() == 'catalog_product_view';
    }

    public function isCatalogPage()
    {
        return $this->getFullActionName() == 'catalog_category_view';
    }

    public function isHomePage()
    {
        return $this->getFullActionName() == 'cms_index_index';
    }

    public function getStoreName()
    {
        /*
        * get store name 
        * http://magento.stackexchange.com/questions/93665/magento-2-get-store-name-in-template
        */
        return $this->_storeConfig->getValue(
            'general/store_information/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getStoreUrl($fromStore = true)
    {
        return $this->_storeManager->getStore()->getCurrentUrl($fromStore);
    }

    public function getWebsiteId($fromStore = true)
    {
        return $this->_storeManager->getStore()->getWebsiteId();
    }

    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }
    
    public function getStoreEmail()
    {
        return $this->_storeConfig->getValue(
            'trans_email/ident_general/email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    protected function _prepareLayout()
    {
    }
        
    public function loadByOrgname($orgName)
    {
        $test = $this->_organizationDetailsFactory->create();
        $id = $test->getResource()->loadByOrgname($orgName);
        //return $test->load($id);
        return $id;
    }
    
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }
    
    public function getCurrentCategory()
    {
        return $this->_registry->registry('current_category');
    }
    
    public function getCurrentProduct()
    {
        return $this->_registry->registry('current_product');
    }

    public function getProductCategories()
    {
        $categoryList =[];
        if ($this->getCurrentProduct()) {
            $categoryIds = $this->getCurrentProduct()->getCategoryIds();
            if (is_array($categoryIds) and count($categoryIds) >= 1) {
                foreach ($categoryIds as $categoryId) {
                    $categoryData = $this->_categoryFactory->create()->load($categoryId);
                    $parentCategories = $categoryData->getParentCategories();
                    $list=[];
                    foreach ($parentCategories as $category) {
                        array_push($list, $category->getName());
                    }
                    $categoryList[$categoryId] =  $list;
                }
            }
        }
        return $categoryList;
    }
}
