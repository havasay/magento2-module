<?php

namespace Havasay\Havasay\Block;

class ConsumersReadBlock
{
    protected $jobName = "cron_consumers";
    protected $cronDbBlock;
    protected $hs_key = "";
    protected $logger;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    protected $sortBuilder;
    protected $filterBuilder;
    protected $customerModel;
    protected $_customerViewHelper;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Backend\Helper\Data $adminhtmlData
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder

     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SortOrderBuilder $sortBuilder,
        \Magento\Customer\Model\Customer $customerModel,
        \Havasay\Havasay\Block\CronJobDbProcess $cronDbBlock,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Helper\View $customerViewHelper
    ) {
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->cronDbBlock = $cronDbBlock;
        $this->sortBuilder = $sortBuilder;
        $this->customerModel = $customerModel;
        $this->_customerViewHelper = $customerViewHelper;
        $this->logger = $logger;
    }

    /**
     * perform remote calls to create consumer for Havasay
     *
     * @param type $havasayObj
     * @return \Havasay\Havasay\Block\ConsumersReadBlock
     */
    public function execute($havasayObj)
    {
        $this->hs_key = sha1($havasayObj['org_secret']);
        $failedData = $this->cronDbBlock->loadFailed($havasayObj['store_id'], $this->jobName, true);
        foreach ($failedData as $record) {
            $this->logger->info("Failed EntityId : " . $record['entity_id']);
            //$customer = $this->customerModel->load($record['entityId']);
            $customer = $this->customerRepository->getById($record['entity_id']);
            $this->handleRemotecall($customer, $havasayObj, $record->getId());
        }
        $lastCustomer = null;
        $model = $this->cronDbBlock->loadCronStatus($havasayObj['store_id'], $this->jobName);
        if (is_null($model)) {
            $model = $this->cronDbBlock->getNewCronStatusObject($havasayObj['store_id'], $this->jobName);
        }
        $this->logger->info("data constraints2 :" . $havasayObj['store_id'] . " : " . $model->getTimestamp());
        $collection = $this->getConsumers($havasayObj['store_id'], $model->getTimestamp());
        foreach ($collection as $customer) {
            $response = $this->handleRemotecall($customer, $havasayObj, 0);
            $this->logger->info(json_encode($response));
            $lastCustomer = $customer;
        }
        if (!is_null($lastCustomer)) {
            $this->cronDbBlock->setCronStatus($model->getId(), $lastCustomer->getId(), $customer->getUpdatedAt());
            $this->logger->info("Processed upto :" . $lastCustomer->getId());
        }
        return $this;
    }

    /**
     * get cosumers for the given storeId updated after given timestamp
     *
     * @param type $storeId
     * @param type $timestamp
     * @return array
     */
    public function getConsumers($storeId, $timestamp)
    {
        $result = [];
        $sortOrder = $this->sortBuilder->setField('updated_at')->setDirection('ASC')->create();
        $this->searchCriteriaBuilder->addSortOrder($sortOrder)->addFilter('store_id', $storeId, 'eq')->addFilter('updated_at', $timestamp, 'gteq');
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->customerRepository->getList($searchCriteria);
        foreach ($searchResults->getItems() as $customer) {
            array_push($result, $customer);
        //    $this->logger->info("id:" . $customer->getId() . "-> updated at :" . $customer->getUpdatedAt() . ", created at :" . $customer->getCreatedAt());
        }
        return $result;
    }

    /**
     *
     * @param type $consumer
     * @param type $havasayObj
     * @param type $recordId
     * @return type
     */
    protected function handleRemotecall($consumer, $havasayObj, $recordId)
    {
        $response = $this->makeRemoteCall($consumer, $havasayObj);
        $responseObj= json_decode($response);
        if (empty($responseObj) || empty($responseObj->status)) {
            $this->cronDbBlock->setFailed($recordId, $havasayObj['store_id'], $this->jobName, $consumer->getId());
        } elseif ($responseObj->status != 200) {
            $this->cronDbBlock->setFailed($recordId, $havasayObj['store_id'], $this->jobName, $consumer->getId(), '', $responseObj->response);
        } else {
            $this->cronDbBlock->setFailedwithId($recordId, false);
        }
        return $responseObj;
    }

    /**
     *
     * @param type $customer
     * @param type $havasayObj
     * @return type
     */
    public function makeRemoteCall($customer, $havasayObj)
    {
        $path = $havasayObj['havasay_path'] . '/magento/consumer/create';
        $data = [
            "name" => $this->_customerViewHelper->getCustomerName($customer),
            "email" => $customer->getEmail(),
            "password" => "f925916e2754e5e03f75dd58a5733251",
            "consumerId" => $customer->getId(),
            "organizationId" => $havasayObj['org_id']
        ];
        $data_string = json_encode($data);
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
        //$this->_logger->info("curl --header".json_encode($header)." --request POST  --data '".$data_string."' ".$path);
        $response = curl_exec($ch);
        return $response;
    }
}
