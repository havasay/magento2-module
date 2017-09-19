<?php

namespace Havasay\Havasay\Block;

class ProductsBlock
{

    protected $jobName = "cron_products";
    protected $cronDbBlock;
    protected $hs_key = "";
    protected $logger;
    protected $categoryFactory;
    protected $productModel;
    protected $productCollectionFactory;
    protected $_catalogProductTypeConfigurable;
    
    /**
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Havasay\Havasay\Block\CronJobDbProcess $cronDbBlock
     * @param \Magento\Catalog\Model\Product $productModel
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        \Havasay\Havasay\Block\CronJobDbProcess $cronDbBlock,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
    ) {
    
        $this->logger = $logger;
        $this->categoryFactory = $categoryFactory;
        $this->cronDbBlock = $cronDbBlock;
        $this->productModel = $productModel;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
    }
    
    /**
     *
     * @param type $havasayObj
     * @return \Havasay\Havasay\Block\ProductsBlock
     */
    public function execute($havasayObj)
    {
        $this->hs_key = sha1($havasayObj['org_secret']);
        $failedData = $this->cronDbBlock->loadFailed($havasayObj['store_id'], $this->jobName, true);
        foreach ($failedData as $record) {
            $product = $this->productModel->load($record['entity_id']);
            $this->handleRemotecall($product, $havasayObj, $record->getId());
        }
        $lastProduct = null;
        $model = $this->cronDbBlock->loadCronStatus($havasayObj['store_id'], $this->jobName);
        if (is_null($model)) {
            $model = $this->cronDbBlock->getNewCronStatusObject($havasayObj['store_id'], $this->jobName);
        }
        $timestamp = $model->getTimestamp();
        $this->logger->info("data constraints :" . $havasayObj['store_id'] . " : " . $timestamp);
        $collection = $this->productCollectionFactory->create()->addStoreFilter($havasayObj['store_id'])->addAttributeToSelect('*');
        if (!is_null($timestamp)) {
            $collection->addFieldToFilter('updated_at', ['gteq' => $timestamp])
                    ->setOrder('updated_at', 'ASC');
        }
        foreach ($collection as $product) {
            $response = $this->handleRemotecall($product, $havasayObj, 0);
            $this->logger->info(json_encode($response));
            $lastProduct = $product;
        }
        if (!is_null($lastProduct)) {
            $this->cronDbBlock->setCronStatus($model->getId(), $lastProduct->getId(), $lastProduct['updated_at']);
            $this->logger->info("Processed upto :" . $lastProduct->getId());
        }
        return $this;
    }
    
    /**
     *
     * @param type $product
     * @return type
     */
    protected function getProductData($product)
    {
        $productData = [];
        $productData['productName'] = $product->getName();
        $productData['baseSku'] = $this->getParentSKU($product->getId());
        $productData['sku'] = $product->getData()['sku'];
        $categoryIds = $product->getCategoryIds();
        $categoryList = [];
        if (is_array($categoryIds) and count($categoryIds) >= 1) {
            foreach ($categoryIds as $categoryId) {
                $categoryList[$categoryId] = $this->buildBreadcrumbList($categoryId);
            }
        }
        $productData['categories'] = $categoryList;
        return $productData;
    }
    
    public function getParentSKU($productId) {
        $parentSku ='';
        $parentByChild = $this->_catalogProductTypeConfigurable->getParentIdsByChild($productId);
        if (isset($parentByChild[0])) {
                $parentProduct = $this->productModel->load($parentByChild[0]);
            if ($parentProduct != null) {
                $parentSku = $parentProduct->getData()['sku'];
                $this->logger->info("parent sku :". $parentProduct->getData()['sku'].";");
            }
        }
        return $parentSku;
    }
   
    /**
     *
     * @param type $categoryId
     * @return array
     */
    protected function buildBreadcrumbList($categoryId)
    {
        $categoryData = $this->categoryFactory->create()->load($categoryId);
        $parentCategories = $categoryData->getParentCategories();
        $list=[];
        foreach ($parentCategories as $category) {
            array_push($list, $category->getName());
        }
        return $list;
    }
    
    /**
     *
     * @param type $product
     * @param type $havasayObj
     * @param type $recordId
     * @return type
     */
    protected function handleRemotecall($product, $havasayObj, $recordId)
    {
        $productData = $this->getProductData($product);
        $response = $this->makeRemoteCall($productData, $havasayObj);
        $responseObj= json_decode($response);
        if (empty($responseObj) || empty($responseObj->status)) {
            $this->cronDbBlock->setFailed($recordId, $havasayObj['store_id'], $this->jobName, $product->getId());
        } elseif ($responseObj->status != 200) {
            $this->cronDbBlock->setFailed($recordId, $havasayObj['store_id'], $this->jobName, $product->getId(), '', $responseObj->response);
        } else {
            $this->cronDbBlock->setFailedwithId($recordId, false);
        }
        return $responseObj;
    }

    /**
     *
     * @param type $productData
     * @param type $havasayObj
     * @return type
     */
    public function makeRemoteCall($productData, $havasayObj)
    {
        $path = $havasayObj['havasay_path'] . '/magento/product/create';
        $productData['channelId'] = $havasayObj['channel_id'];
        $productData['organizationId'] = $havasayObj['org_id'];
        $productData['listName'] = $havasayObj['list_name'];
        $data_string = json_encode($productData);
        $ch = curl_init($path);
        $header = [
            'Content-Type: application/json',
            'orgKey : ' . $havasayObj['org_key'],
            'x-hs-party : ' . $havasayObj['org_id'],
            'x-hs-key : ' . $this->hs_key,
            'Content-Length: ' . strlen($data_string)];
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        //$this->logger->info("curl --header".json_encode($header)." --request POST  --data '".$data_string."' ".$path);
        return curl_exec($ch);
    }
}
