<?php
namespace Havasay\Havasay\Block;

class CategoriesListBlock extends \Magento\Framework\View\Element\Template
{

    
    protected $_categoryCollectionFactory;
    protected $_categoryHelper;
           
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Helper\Category $categoryHelper,
        array $data = []
    ) {
    
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_categoryHelper = $categoryHelper;
        parent::__construct($context, $data);
    }
     /**
      * Get category collection
      * @param int $storeId
      * @param date&time $timestamp
      * @param bool $isActive
      * @param bool|int $level
      * @param bool|string $sortBy
      * @param bool|int $pageSize
      * @return \Magento\Catalog\Model\ResourceModel\Category\Collection or array
      */
    public function getCategoryCollection($storeId, $timestamp, $level = false, $sortBy = false, $pageSize = false)
    {
        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        if (!is_null($timestamp)) {
            $collection->addFieldToFilter('updated_at', ['gteq' => $timestamp])
               ->setOrder('sort_order', 'ASC');
        }
        if (!is_null($storeId)) {
            $collection->setStore($storeId);
        }
        //if ($isActive) { $collection->addIsActiveFilter(); }
        // select categories of certain level
        if ($level) {
            $collection->addLevelFilter($level);
        }
        // sort categories by some value
        if ($sortBy) {
            $collection->addOrderField($sortBy);
        }
        // select certain number of categories
        if ($pageSize) {
            $collection->setPageSize($pageSize);
        }
        return $collection;
    }
    
    /**
     * Retrieve current store categories
     *
     * @param bool|string $sorted
     * @param bool $asCollection
     * @param bool $toLoad
     * @return \Magento\Framework\Data\Tree\Node\Collection or
     * \Magento\Catalog\Model\ResourceModel\Category\Collection or array
     */
    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        return $this->_categoryHelper->getStoreCategories($sorted, $asCollection, $toLoad);
    }
 
    /**
     * Retrieve store categories based on timestamp
     *
     * @param int $storeId
     * @param date&time $timestamp
     * @return \Magento\Framework\Data\Tree\Node\Collection or
     * \Magento\Catalog\Model\ResourceModel\Category\Collection or array
     */
    public function getList($storeId, $timestamp)
    {
        return $this->getCategoryCollection($storeId, $timestamp, false, false, false);
    }
}
