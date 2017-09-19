<?php

namespace Havasay\Havasay\Block;

use Magento\Framework\Event\ObserverInterface;

//use Magento\Framework\DataObject as Object;

class ReviewFollowupController implements ObserverInterface
{

    protected $_logger;
    protected $_jobName;
    protected $_modelOrganizationDetailsFactory;
    protected $_customerRepository;
    protected $_havasayCallUtil;
    protected $_productModel;
    protected $_customerViewHelper;
    protected $_categoryFactory;
    protected $productsProcess;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Helper\View $customerViewHelper,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\Product $productModel,
        \Havasay\Havasay\Block\ProductsBlock $productsBlock,
        \Havasay\Havasay\Block\HavasayCurlCallUtil $havasayCall,
        \Havasay\Havasay\Model\OrganizationDetailsFactory $modelOrganizationDetailsFactory
    ) {
        $this->_logger = $logger;
        $this->_havasayCallUtil = $havasayCall;
        $this->_categoryFactory = $categoryFactory;
        $this->_customerRepository = $customerRepository;
        $this->_productModel = $productModel;
        $this->_customerViewHelper = $customerViewHelper;
        $this->_modelOrganizationDetailsFactory = $modelOrganizationDetailsFactory;
        $this->productsProcess = $productsBlock;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->_jobName = "order_complete_event";
        $order = $observer->getEvent()->getOrder();

        if (!$order instanceof \Magento\Framework\Model\AbstractModel) {
            return $this;
        }
        if ($order->getState() != 'complete') {  //new, processing,
            return $this;
        }
        $customer = $this->_customerRepository->getById($order->getCustomerId());
        $this->_logger->info('storeId :' . $order->getStoreId() . '; order_date' . $order->getCreatedAt() . '; customer mailId ' . $customer->getEmail() . " id:" . $customer->getId());
        $object = $this->getHavasayDetailsObject($order->getStoreId());
        if ($object) {
            $this->processEventCall($object, $order, $customer);
        }
        return $this;
    }

    protected function getHavasayDetailsObject($storeId)
    {
        $data = $this->_modelOrganizationDetailsFactory->create()->getCollection()->addFieldToFilter('store_id', ['eq' => $storeId]);
        if (is_null($data) || $data->count() < 1) {
            $this->_logger->debug('havasay details not exists for storeid :' . $storeId);
            return null;
        }
        return $data->getFirstItem()->getData();
    }

    public function getProductData($productId)
    {
        $product = $this->_productModel->load($productId);
        $categoryIds = $product->getCategoryIds();
        $categoryList = [];
        if (is_array($categoryIds) and count($categoryIds) >= 1) {
            foreach ($categoryIds as $categoryId) {
                $categoryList[$categoryId] = $this->buildBreadcrumbList($categoryId);
            }
        }
        $productData = [];
        $productData['baseSku'] = $this->productsProcess->getParentSKU($product->getId());
        $productData['productName'] = $product->getName();
        $productData['sku'] = $product->getData()['sku'];
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
        $categoryData = $this->_categoryFactory->create()->load($categoryId);
        $parentCategories = $categoryData->getParentCategories();
        $list = [];
        foreach ($parentCategories as $category) {
            array_push($list, $category->getName());
        }
        return $list;
    }

    protected function processEventCall($havasayObj, $order, $customer)
    {
        $items = $order->getAllItems();
        $order_number = $order->getIncrementId();
        $itemDetails = $this->getItemDetails($items);
        $reqData = [
            "invoiceNumber" => $order_number,
            "itemDetails" => $itemDetails,
            "emailId" => $customer->getEmail(),
            "completedDate" => date("Y-m-d H:i:s"), //$currentdate,
            "orderDate" => $order->getCreatedAt(),
            "channelId" => $havasayObj['channel_id'],
            "organizationId" => $havasayObj['org_id'],
            "status" => $order->getState(),
            "consumerName" => $this->_customerViewHelper->getCustomerName($customer)
        ];
        $this->_logger->info("requestData :".json_encode($reqData));
        $this->_havasayCallUtil->makeHavasayRemoteCall($reqData, $this->_jobName, $havasayObj);
    }

    protected function getItemDetails($items)
    {
        $itemDetails = [];

        foreach ($items as $item) {
            $productData = $this->getProductData($item->getId());
            $this->_logger->info('product sku : ' . $item->getSku());
            $itemData = [
                "itemCode" => $item->getSku(),
                "productName" => $productData['productName'],
                "categories" => $productData['categories']
            ];
            array_push($itemDetails, $itemData);
        }
        return $itemDetails;
    }
}
