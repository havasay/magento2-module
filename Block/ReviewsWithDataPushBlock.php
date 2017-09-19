<?php

namespace Havasay\Havasay\Block;

class ReviewsWithDataPushBlock
{

    protected $cronDbBlock;
    protected $reviewModel;
    protected $_logger;
    protected $customerFactory;
    protected $customerModel;
    protected $productFactory;
    protected $productModel;
    protected $categoryFactory;
    protected $customerRepository;
    protected $_customerViewHelper;
    protected $skippedProductTypes = ['configurable'];
    protected $productsProcess;

    public function __construct(\Psr\Log\LoggerInterface $logger, \Magento\Customer\Helper\View $customerViewHelper,  \Havasay\Havasay\Block\ProductsBlock $productsBlock, \Havasay\Havasay\Block\CronJobDbProcess $cronDbBlock, \Magento\Customer\Model\CustomerFactory $_customerFactory, \Magento\Review\Model\Review $reviewModel, \Magento\Catalog\Model\Product $productModel, \Magento\Catalog\Model\CategoryFactory $categoryFactory, \Magento\Catalog\Model\ProductFactory $productFactory, \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    )
    {
        $this->_logger = $logger;
        $this->cronDbBlock = $cronDbBlock;
        $this->reviewModel = $reviewModel;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $_customerFactory;
        $this->productFactory = $productFactory;
        $this->categoryFactory = $categoryFactory;
        $this->productModel = $productModel;
        $this->_customerViewHelper = $customerViewHelper;
        $this->productsProcess = $productsBlock;
    }

    /**
     *
     * @param type $havasayObj
     * @return \Havasay\Havasay\Block\ReviewsBlock
     */
    public function execute($havasayObj)
    {
        $jobName = "cron_reviews";
        $this->customerModel = $this->customerFactory->create();
        $lastReviewData = null;
        $model = $this->cronDbBlock->loadCronStatus($havasayObj['store_id'], $jobName);
        if (is_null($model)) {
            $model = $this->cronDbBlock->getNewCronStatusObject($havasayObj['store_id'], $jobName);
        }
        $this->_logger->info("start time of reviews for the store id :" . $havasayObj['store_id'] . " timestamp >  " . $model->getTimestamp());
        $collection = $this->getReviewsDataList($havasayObj['store_id'], $model->getTimestamp());
        $x = 0;
        foreach ($collection as $reviewTotalData) {
            if (!empty($reviewTotalData['reviewData'])) {
                $presponseObj = $this->handleProductcall($reviewTotalData['productData'], $havasayObj, 0);
                if ($presponseObj->status == 200) {
                    $cresponseObj = $this->handleConsumercall($reviewTotalData['customerData'], $havasayObj, 0);
                    if ($cresponseObj->status == 200 || $cresponseObj->response == 'Havasay account already exists for this email Id') {
                        $responseObj = $this->handleReviewcall($reviewTotalData['reviewData'], $havasayObj, 0);
                        $lastReviewData = $reviewTotalData['reviewData'];
                        if ($responseObj->status == 200) {
                            $x++;
                        }
                    }
                }
                if ($x == 50) {
                    break;
                }
            }
        }
        if (!is_null($lastReviewData)) {
            $this->cronDbBlock->setCronStatus($model->getId(), $lastReviewData['id'], $lastReviewData['created_at']);
            $this->_logger->info("Processed upto :" . $lastReviewData['id']);
        }
        return $this;
    }

    public function getReviewsDataList($storeId, $timestamp)
    {
        $reviewList = [];
        $reviews = $this->reviewModel->getCollection()->addFieldToFilter('store_id', ['eq' => $storeId])
                        ->addFieldToFilter('created_at', ['gteq' => $timestamp])->setOrder('created_at', 'ASC')->addRateVotes();
        foreach ($reviews as $review) {
            $text = $review->getDetail();
            if (strlen($text) > 1) {
                $rating = 0;
                $this->_logger->info("Before Prepareing Review Data:");
                $reviewTotalData = $this->prepareReviewData($review);
                if (empty($reviewTotalData)) {
                    continue;
                }
                $reviewData = $reviewTotalData['reviewData'];
                if (!empty($reviewData)) {
                    $votes = $review->getRatingVotes();
                    foreach ($votes as $vote) {
                        if ($vote->getPercent() > 0) {
                            $rating = $vote->getPercent() / 20; //$vote->getId()
                        }
                    }
                    $reviewData['rating'] = $rating;
                    $reviewTotalData['reviewData'] = $reviewData;
                    array_push($reviewList, $reviewTotalData);
                }
            }
        }
        return $reviewList;
    }

    /**
     *
     * @param type $review
     * @return type
     */
    protected function prepareReviewData($review)
    {
        $reviewTotalData = [];
        if ($review->getEntityId() != 1) {// if entity 1 is product  i.e. review is not for product
            return $reviewTotalData;
        }
        $customerId = $review->getCustomerId();
        if (is_null($customerId) || strlen($customerId) <= 0 || $customerId == 0) {
            $this->_logger->info(" In valid customer id " . $customerId);
            return $reviewTotalData;
        }
        $productId = $review->getEntityPkValue();
        if (is_null($productId) || strlen($productId) <= 0 || $productId == 0) {
            $this->_logger->info(" In valid productId " . $productId);
            return $reviewTotalData;
        }
        $product = $this->productModel->load($productId);
        if (is_null($product)) {
            return $reviewTotalData;
        }
        $reviewTotalData['productData'] = $this->getProductData($product);
        $customer = $this->customerRepository->getById($customerId);
        if (is_null($customer)) {
            return $reviewTotalData;
        }
        $reviewTotalData['customerData'] = $customer; // need customer interface to get nickname
        $inputData = [
            "id" => $review->getId(),
            "reviewTitle" => $review->getTitle(),
            "reviewText" => $review->getDetail(),
            "name" => $review->getNickname(),
            "emailId" => $customer->getEmail(),
            "created_at" => $review->getCreatedAt(),
            "statusId" => $review->getStatusId(),
            "productId" => $product->getData('sku')
        ];
        $reviewTotalData['reviewData'] = $inputData;
        return $reviewTotalData;
    }

    protected function getProductData($product)
    {
        $productData = [];
        $productData['productName'] = $product->getName();
        $productData['baseSku'] = $this->productsProcess->getParentSKU($product->getId());
        $productData['sku'] = $product->getData()['sku'];
        $categoryIds = $product->getCategoryIds();
        $categoryList = [];
        if (is_array($categoryIds) and count($categoryIds) >= 1) {
            foreach ($categoryIds as $categoryId) {
                $categoryList[$categoryId] = $this->buildBreadcrumbList($categoryId);
            }
        }
        $productData['id'] = $product->getId();
        $productData['categories'] = $categoryList;
        return $productData;
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
        $list = [];
        foreach ($parentCategories as $category) {
            array_push($list, $category->getName());
        }
        return $list;
    }

    /**
     *
     * @param type $storeId
     * @param type $timestamp
     * @return array
     */
    protected function handleProductcall($productData, $havasayObj, $recordId)
    {
        $jobName = "cron_products";
        $response = $this->makeProductCall($productData, $havasayObj);
        return $this->handleResponseObject($response, $recordId, $havasayObj['store_id'], $jobName, $productData['id']);
    }

    protected function handleConsumercall($consumer, $havasayObj, $recordId)
    {
        $jobName = "cron_consumers";
        $response = $this->makeConsumerCall($consumer, $havasayObj);
        return $this->handleResponseObject($response, $recordId, $havasayObj['store_id'], $jobName, $consumer->getId());
    }

    protected function handleReviewcall($reviewData, $havasayObj, $recordId)
    {
        $jobName = "cron_reviews";
        $response = $this->makeReviewCall($reviewData, $havasayObj);
        return $this->handleResponseObject($response, $recordId, $havasayObj['store_id'], $jobName, $reviewData['id']);
    }

    protected function handleResponseObject($response, $recordId, $storeId, $jobName, $entityId)
    {
        $responseObj = json_decode($response);
        if (empty($responseObj) || empty($responseObj->status)) {
            $this->cronDbBlock->setFailed($recordId, $storeId, $jobName, $entityId);
        } elseif ($responseObj->status != 200) {
            $this->cronDbBlock->setFailed($recordId, $storeId, $jobName, $entityId, '', $responseObj->response);
        } else {
            $this->cronDbBlock->setFailedwithId($recordId, false);
        }
        $this->_logger->info("after call:" . $jobName . ">" . "status :" . $responseObj->status);
        return $responseObj;
    }

    public function makeReviewCall($reviewData, $havasayObj)
    {
        $path = $havasayObj['havasay_path'] . '/magento/review/create';
        $reviewData['channelId'] = $havasayObj['channel_id'];
        $reviewData['organizationId'] = $havasayObj['org_id'];
        return $this->makeRemoteCall($reviewData, $path, $havasayObj);
    }

    public function makeProductCall($productData, $havasayObj)
    {
        $path = $havasayObj['havasay_path'] . '/magento/product/create';
        $productData['channelId'] = $havasayObj['channel_id'];
        $productData['organizationId'] = $havasayObj['org_id'];
        $productData['listName'] = $havasayObj['list_name'];
        return $this->makeRemoteCall($productData, $path, $havasayObj);
    }

    public function makeConsumerCall($customer, $havasayObj)
    {
        $path = $havasayObj['havasay_path'] . '/magento/consumer/create';
        $data = [
            "name" => $this->_customerViewHelper->getCustomerName($customer),
            "email" => $customer->getEmail(),
            "password" => "f925916e2754e5e03f75dd58a5733251",
            "consumerId" => $customer->getId(),
            "organizationId" => $havasayObj['org_id']
        ];
        return $this->makeRemoteCall($data, $path, $havasayObj);
    }

    public function makeRemoteCall($data, $path, $havasayObj)
    {
        $data_string = json_encode($data);
        $ch = curl_init($path);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $header = [
            'Content-Type: application/json',
            'orgKey : ' . $havasayObj['org_key'],
            'x-hs-party : ' . $havasayObj['org_id'],
            'x-hs-key : ' . sha1($havasayObj['org_secret']),
            'Content-Length: ' . strlen($data_string)];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        return curl_exec($ch);
    }
}
