<?php
namespace Havasay\Havasay\Block;

/**
 *
 */
class CategoriesProcessBlock extends \Magento\Framework\View\Element\Template
{

    protected $jobName = "cron_categories";
    protected $logger;
    protected $categoriesBlock;
    protected $categoryFactory;
    protected $cronDbBlock;
    protected $hs_key = "";
        
    /**
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Havasay\Havasay\Block\CategoriesListBlock $categoriesBlock
     * @param \Havasay\Havasay\Block\CategoriesReadBlock $categoriesDataBlock
     * @param \Havasay\Havasay\Block\CronJobDbProcess $cronDbBlock
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Havasay\Havasay\Block\CategoriesListBlock $categoriesBlock,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Havasay\Havasay\Block\CronJobDbProcess $cronDbBlock
    ) {
    
        $this->logger = $logger;
        $this->categoriesBlock = $categoriesBlock;
        $this->categoryFactory = $categoryFactory;
        $this->cronDbBlock = $cronDbBlock;
    }

    /**
     * execute method read categories for the given store and perform curl insert to the given Havsay path .
     *
     * @param type $havasayObj
     */
    public function execute($havasayObj)
    {
        $this->hs_key = sha1($havasayObj['org_secret']);
        $failedData = $this->cronDbBlock->loadFailed($havasayObj['store_id'], $this->jobName, true);
        foreach ($failedData as $record) {
            $this->handleRemotecall($record['entityId'], $havasayObj, $record->getId());
        }
        $lastCategory = null;
        $timestamp = null;
        $model = $this->cronDbBlock->loadCronStatus($havasayObj['store_id'], $this->jobName);
        if (!is_null($model)) {
            $this->logger->info($model['timestamp'].":".$model->getId().":".$model->getTimestamp());
            $timestamp = $model->getTimestamp();
        }
        $this->logger->info("data constraints :".$havasayObj['store_id']." : ". $timestamp);
        $categories  = $this->categoriesBlock->getList($havasayObj['store_id'], $timestamp);
        if (is_null($model)) {
             $model = $this->cronDbBlock->getNewCronStatusObject($havasayObj['store_id'], $this->jobName);
        }
        foreach ($categories as $category) {
            $this->handleRemotecall($category->getId(), $havasayObj, 0);
            $lastCategory = $category;
        }
        if (!is_null($lastCategory)) {
            $this->cronDbBlock->setCronStatus($model->getId(), $lastCategory->getId(), $lastCategory['updated_at']);
            $this->logger->info("Processed upto :". $lastCategory->getId());
        }
    }
    
    /**
     * This method prepares breadcrumb for the given categoryid
     *
     * @param type $categoryId
     * @return array
     */
    protected function buildBreadcrumbList($categoryId)
    {
        $categoryData = $this->categoryFactory->create()->load($categoryId);
        $parentCategories = $categoryData->getParentCategories();
        $list=[];
        foreach ($parentCategories as $categoryStr) {
            array_push($list, $categoryStr->getName());
        }
        return $list;
    }

    /**
     * this method handles the remote call and response verification
     *
     * @param type $categoryId
     * @param type $havasayObj
     * @param type $recordId
     * @return type
     */
    protected function handleRemotecall($categoryId, $havasayObj, $recordId)
    {
        $list = $this->buildBreadcrumbList($categoryId);
        $response =  $this->makeCategoryRemoteCall($list, $havasayObj);
        $responseObj= json_decode($response);
        if (empty($response) || empty($responseObj->status)) {
              $this->cronDbBlock->setFailed($recordId, $havasayObj['store_id'], $this->jobName, $categoryId);
        } elseif ($responseObj->status != 200) {
             $this->cronDbBlock->setFailed($recordId, $havasayObj['store_id'], $this->jobName, $categoryId, '', $responseObj->response);
        } else {
             $this->cronDbBlock->setFailedwithId($recordId, false);
        }
        $this->logger->info(json_encode($responseObj));
        return $responseObj;
    }
    
    /**
     *  perform category creation call to Havasay
     *
     * @param type $category
     * @param type $havasayObj
     * @return type
     */
    public function makeCategoryRemoteCall($category, $havasayObj)
    {
        $path =  $havasayObj['havasay_path'].'/magento/category/create';
        $data = [ "category" => $category,
            "listName" => $havasayObj['list_name'],
            "organizationId" => $havasayObj['org_id']];
        $response = $this->makeRemoteCall($data, $path, $havasayObj);
        return $response;
    }
    
    /**
     * perform remote call to the Havasay
     *
     * @param type $data
     * @param type $path
     * @param type $havasayObj
     * @return type
     */
    public function makeRemoteCall($data, $path, $havasayObj)
    {
        $data_string = json_encode($data);
        $ch = curl_init($path);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $header = [
            'Content-Type: application/json',
            'orgKey : '.$havasayObj['org_key'],
            'x-hs-party : '.$havasayObj['org_id'],
            'x-hs-key : '.sha1($havasayObj['org_secret']),
            'Content-Length: ' . strlen($data_string)] ;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        return curl_exec($ch);
    }
}
