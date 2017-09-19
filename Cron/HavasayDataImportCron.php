<?php

namespace Havasay\Havasay\Cron;

class HavasayDataImportCron
{

    protected $logger;
    protected $categoriesProcess;
    protected $consumersProcess;
    protected $productsProcess;
    protected $reviewsProcess;
    protected $_modelOrganizationDetailsFactory;

    /**
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Havasay\Havasay\Block\ConsumersReadBlock $consumersBlock
     * @param \Havasay\Havasay\Block\ReviewsBlock $reviewsBlock
     * @param \Havasay\Havasay\Block\ProductsBlock $productsBlock
     * @param \Havasay\Havasay\Block\CategoriesProcessBlock $readBlock
     * @param \Havasay\Havasay\Model\OrganizationDetailsFactory $modelOrganizationDetailsFactory
     */
    public function __construct(\Psr\Log\LoggerInterface $logger, \Havasay\Havasay\Block\ConsumersReadBlock $consumersBlock, \Havasay\Havasay\Block\ReviewsBlock $reviewsBlock, \Havasay\Havasay\Block\ProductsBlock $productsBlock, \Havasay\Havasay\Block\CategoriesProcessBlock $readBlock, \Havasay\Havasay\Model\OrganizationDetailsFactory $modelOrganizationDetailsFactory)
    {
        $this->logger = $logger;
        $this->categoriesProcess = $readBlock;
        $this->consumersProcess = $consumersBlock;
        $this->productsProcess = $productsBlock;
        $this->reviewsProcess = $reviewsBlock;
        $this->_modelOrganizationDetailsFactory = $modelOrganizationDetailsFactory;
    }

    /**
     *
     * @return \Havasay\Havasay\Cron\CategoriesCron
     */
    public function execute()
    {
        $this->logger->info("Before importing magento store data to  havasay.");
        $storeCollection = $this->_modelOrganizationDetailsFactory->create()->getCollection();
        foreach ($storeCollection as $obj) {
            $this->categoriesProcess->execute($obj);
            $this->consumersProcess->execute($obj);
            $this->productsProcess->execute($obj);
            $this->reviewsProcess->execute($obj);
        }
        $this->logger->info("After importing magento store data to  havasay.");
        return $this;
    }
}
