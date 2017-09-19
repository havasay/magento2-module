<?php

namespace Havasay\Havasay\Block;

class ReviewsBlock
{

    protected $jobName = "cron_reviews";
    protected $cronDbBlock;
    protected $hs_key = "";
    protected $reviewModel;
    protected $logger;
    protected $customerFactory;
    protected $customerModel;
    protected $productFactory;
    protected $productModel;
    protected $skippedProductTypes = ['configurable'];

   /**
    *
    * @param \Psr\Log\LoggerInterface $_logger
    * @param \Havasay\Havasay\Block\CronJobDbProcess $cronDbBlock
    * @param \Magento\Customer\Model\CustomerFactory $_customerFactory
    * @param \Magento\Review\Model\Review $reviewModel
    * @param \Magento\Catalog\Model\Product $productModel
    * @param \Magento\Catalog\Model\ProductFactory $productFactory
    */
    public function __construct(\Psr\Log\LoggerInterface $_logger, \Havasay\Havasay\Block\CronJobDbProcess $cronDbBlock, \Magento\Customer\Model\CustomerFactory $_customerFactory, \Magento\Review\Model\Review $reviewModel, \Magento\Catalog\Model\Product $productModel, \Magento\Catalog\Model\ProductFactory $productFactory
    )
    {
        $this->logger = $_logger;
        $this->cronDbBlock = $cronDbBlock;
        $this->reviewModel = $reviewModel;
        $this->customerFactory = $_customerFactory;
        $this->productFactory = $productFactory;
        $this->productModel = $productModel;
    }
    
    /**
     *
     * @param type $havasayObj
     * @return \Havasay\Havasay\Block\ReviewsBlock
     */
    public function execute($havasayObj)
    {
        $this->hs_key = sha1($havasayObj['org_secret']);
        $this->customerModel = $this->customerFactory->create();
      
        $failedData = $this->cronDbBlock->loadFailed($havasayObj['store_id'], $this->jobName, true);
        foreach ($failedData as $record) {
            $review = $this->reviewModel->load($record['entity_id']);
            $reviewData = $this->prepareReviewData($review);
            if (!empty($reviewData)) {
                $reviewData['rating'] = $record['additionalData'];
                $this->handleRemotecall($reviewData, $havasayObj, $record->getId());
            }
        }
        $lastReviewData = null;
        $model = $this->cronDbBlock->loadCronStatus($havasayObj['store_id'], $this->jobName);
        if (is_null($model)) {
            $model = $this->cronDbBlock->getNewCronStatusObject($havasayObj['store_id'], $this->jobName);
        }
        $this->logger->info("data constraints :" . $havasayObj['store_id'] . " : " . $model->getTimestamp());
        $collection = $this->getReviewsDataList($havasayObj['store_id'], $model->getTimestamp());
        foreach ($collection as $reviewData) {
            $response = $this->handleRemotecall($reviewData, $havasayObj, 0);
            $this->logger->info(json_encode($response));
            $lastReviewData = $reviewData;
        }
        if (!is_null($lastReviewData)) {
            $this->cronDbBlock->setCronStatus($model->getId(), $lastReviewData['id'], $lastReviewData['created_at']);
            $this->logger->info("Processed upto :" . $lastReviewData['id']);
        }
        return $this;
    }

    /**
     *
     * @param type $review
     * @return type
     */
    protected function prepareReviewData($review)
    {
        $inputData = [];
        if ($review->getEntityId() == 1) { // if entity is product
            $customerId = $review->getCustomerId();
            if ($customerId > 0) {
                $reviewCustomer = $this->customerModel->load($customerId);
                $data = $reviewCustomer->getData(); //$data['email']
                $productId = $review->getEntityPkValue();
                if ($productId && ($productId > 0)) {
                    $product = $this->productModel->load($productId);
                    if (!is_null($product)) {
                        $this->logger->info("Data type :" . $product->getData('type_id'));
                        $productType = $product->getData('type_id');
                       // if (!in_array($productType, $this->skippedProductTypes)) {
                            $sku = $product->getData('sku');
                            $inputData = [
                                "id" => $review->getId(),
                                "reviewTitle" => $review->getTitle(),
                                "reviewText" => $review->getDetail(),
                                "name" => $review->getNickname(),
                                "emailId" => $data['email'],
                                "created_at" => $review->getCreatedAt(),
                                "statusId" => $review->getStatusId(),
                                "productId" => $sku
                            ];
                            return $inputData;
                       // } else {
                            //$this->logger->info("Product is configurable");
                       // }
                    } else {
                        //$this->logger->info("Product is not available with the given id");
                    }
                }
            } else {
                //$this->logger->info("Review $x: >  CustomerId : guest");
            }
        }
        return $inputData;
    }

    /**
     *
     * @param type $storeId
     * @param type $timestamp
     * @return array
     */
    public function getReviewsDataList($storeId, $timestamp)
    {
        $reviewList = [];
        $reviews = $this->reviewModel->getCollection()->addFieldToFilter('store_id', ['eq' => $storeId])
                        ->addFieldToFilter('created_at', ['gteq' => $timestamp])->setOrder('created_at', 'ASC')->addRateVotes();
        foreach ($reviews as $review) {
            $rating = 0;
            $reviewData = $this->prepareReviewData($review);
            if (!empty($reviewData)) {
                $votes = $review->getRatingVotes();
                foreach ($votes as $vote) {
                    if ($vote->getPercent() > 0) {
                        $rating = $vote->getPercent() / 20; //$vote->getId()
                    }
                }
                $reviewData['rating'] = $rating;
                array_push($reviewList, $reviewData);
            }
        }
        return $reviewList;
    }
    
    /**
     *
     * @param type $reviewData
     * @param type $havasayObj
     * @param type $recordId
     * @return type
     */
    protected function handleRemotecall($reviewData, $havasayObj, $recordId)
    {
        $response = $this->makeRemoteCall($reviewData, $havasayObj);
        $responseObj= json_decode($response);
        if (empty($responseObj) || empty($responseObj->status)) {
            $this->cronDbBlock->setFailed($recordId, $havasayObj['store_id'], $this->jobName, $reviewData['id'], $reviewData['rating']);
        } elseif ($responseObj->status!= 200) {
            $this->cronDbBlock->setFailed($recordId, $havasayObj['store_id'], $this->jobName, $reviewData['id'], $reviewData['rating'], $responseObj->response);
        } else {
            $this->cronDbBlock->setFailedwithId($recordId, false);
        }
        return $responseObj;
    }

    /**
     *
     * @param type $reviewData
     * @param type $havasayObj
     * @return type
     */
    public function makeRemoteCall($reviewData, $havasayObj)
    {
        $path = $havasayObj['havasay_path'] . '/magento/review/create';
        $reviewData['channelId'] = $havasayObj['channel_id'];
        $reviewData['organizationId'] = $havasayObj['org_id'];
        $data_string = json_encode($reviewData);
        $header = [
            'Content-Type: application/json',
            'orgKey : ' . $havasayObj['org_key'],
            'x-hs-party : ' . $havasayObj['org_id'],
            'x-hs-key : ' . $this->hs_key,
            'Content-Length: ' . strlen($data_string)];
        $ch = curl_init($path);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        return curl_exec($ch);
    }
}
