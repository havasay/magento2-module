<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Havasay\Havasay\Block;

/**
 * Description of HavasayCurlCall
 *
 * @author sdussa
 */
class HavasayCurlCallUtil
{

    protected $_entityEndPaths = [];
    protected $_logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_logger = $logger;
        $this->_entityEndPaths = [
            'cron_categories' => '/magento/category/create',
            'cron_products' => '/magento/product/create',
            'cron_consumers' => '/magento/consumer/create',
            'cron_reviews' => '/magento/review/create',
            'order_complete_event' => '/magento/review/followup'
        ];
    }

    /**
     * perform remote call to the Havasay
     *
     * @param type $data
     * @param type $path
     * @param type $havasayObj
     * @return type
     */
    public function makeHavasayRemoteCall($data, $jobName, $havasayObj)
    {
        $data_string = json_encode($data);
        $ch = curl_init($havasayObj['havasay_path'] . $this->_entityEndPaths[$jobName]);
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
