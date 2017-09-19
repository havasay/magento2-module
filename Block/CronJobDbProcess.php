<?php

namespace Havasay\Havasay\Block;

use Havasay\Havasay\Model\CronStatusFactory;
use Havasay\Havasay\Model\CronEntityStatusFactory;

class CronJobDbProcess
{

    protected $logger;
    protected $cronStatusFactory;
    protected $cronEntityStatusFactory;

    /**
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param CronStatusFactory $cronStatusFactory
     * @param CronEntityStatusFactory $cronEntityStatusFactory
     */
    public function __construct(\Psr\Log\LoggerInterface $logger, CronStatusFactory $cronStatusFactory, CronEntityStatusFactory $cronEntityStatusFactory
    )
    {
        $this->logger = $logger;
        $this->cronStatusFactory = $cronStatusFactory;
        $this->cronEntityStatusFactory = $cronEntityStatusFactory;
    }

    /**
     *
     * @param type $storeId
     * @param type $jobName
     * @param type $failedStatus
     * @return array
     */
    public function loadFailed($storeId, $jobName, $failedStatus)
    {
        $this->logger->info("inside loadFailed" . $storeId . ":" . $jobName);
        $items = $this->cronEntityStatusFactory->create()->getCollection()->addFieldToFilter('store_id', ['eq' => $storeId])
                        ->addFieldToFilter('job_name', ['eq' => $jobName])->addFieldToFilter('failed', ['eq' => $failedStatus]);
        $data = [];
        foreach ($items as $item) {
            array_push($data, $item);
        }
        return $data;
    }

    /**
     *
     * @param type $storeId
     * @param type $jobName
     * @param type $entityId
     * @param type $addtionalData
     */
    public function setFailed($recordId, $storeId, $jobName, $entityId, $addtionalData = '', $message = '')
    {
        if ($recordId != 0) {
            return;
        }
        $model = $this->cronEntityStatusFactory->create();
        $model->setData('store_id', $storeId);
        $model->setData('job_name', $jobName);
        $model->setData('entity_id', $entityId);
        $model->setData('failed', true);
        $model->setData('additional_data', $addtionalData);
        $model->setData('error_message', $message);
        $model->save();
        $this->logger->info("inside setFailed storeId:" . $storeId . ": jobName:" . $jobName . ": entityId " . $entityId);
    }

    /**
     *
     * @param type $id
     * @param type $status
     */
    public function setFailedwithId($id, $status)
    {
        if ($id == 0) {
            return;
        }
        $cronEntityModel = $this->cronEntityStatusFactory->create();
        $item = $cronEntityModel->load($id);
        if ($status) {
            $item->setData('failed', $status);
            $item->save();
        } else {
            $item->delete();
        }
        $this->logger->info("inside setFailed entityId:" . $id . ": status: " . $status);
    }

    /**
     *
     * @param type $storeId
     * @param type $jobName
     * @return type
     */
    public function loadCronStatus($storeId, $jobName)
    {
        $items = $this->cronStatusFactory->create()->getCollection()->addFieldToFilter('store_id', ['eq' => $storeId])
                ->addFieldToFilter('job_name', ['eq' => $jobName]);
        foreach ($items as $item) {
            return $item;
        }
        return null;
    }

    /**
     *
     * @param type $modelId
     * @param type $entityId
     * @param type $timestamp
     */
    public function setCronStatus($modelId, $entityId, $timestamp)
    {
        $cronModel = $this->cronStatusFactory->create();
        $item = $cronModel->load($modelId);
        $item->setData('entity_id', $entityId);
        $item->setData('timestamp', $timestamp);
        $item->save();
        $this->logger->info("inside setCronStatus " . $entityId . ":" . $timestamp . "  data updated");
    }

    /**
     *
     * @param type $storeId
     * @param type $jobName
     * @return type
     */
    public function getNewCronStatusObject($storeId, $jobName)
    {
        $model = $this->cronStatusFactory->create();
        $model->setData('store_id', $storeId);
        $model->setData('job_name', $jobName);
        $model->setData('timestamp', "0000-00-00 00:00:00");
        $model->setData('executed_at', date("Y-m-d H:i:s"));
        $model->save();
        return $model;
    }
}
