<?php
namespace Havasay\Havasay\Block;

class Havasay extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Havasay\Havasay\Block\Config $config,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_config = $config;
        $this->_imageHelper = $imageHelper;
        parent::__construct($context, $data);
    }

    public function getProduct()
    {
        if (!$this->hasData('product')) {
            $this->setData('product', $this->_coreRegistry->registry('current_product'));
        }
        return $this->getData('product');
    }

    public function getProductId()
    {
        return $this->getProduct()->getId();
    }

    public function getProductName()
    {
        $productName = $this->escapeString($this->getProduct()->getName());
        return htmlspecialchars($productName);
    }

    public function getProductDescription()
    {
        return $this->escapeString($this->getProduct()->getShortDescription());
    }

    public function getProductUrl()
    {
        return $this->getProduct()->getProductUrl();
    }

    public function isRenderTabWidget()
    {
        return $this->getProduct() != null &&
        ($this->_config->isTabWidgetEnabled() || $this->getData('fromHelper'));
    }

    public function isRenderAggregateWidget()
    {
        return $this->_config->isAggregateWidgetEnabled();
    }

    public function isRenderSharewidget()
    {
        return $this->_config->isSharewidgetEnabled();
    }

    public function getProductImageUrl()
    {
        return $this->_imageHelper->init($this->getProduct(), 'product_page_image_large')->getUrl();
    }
    
    private function isProductPage()
    {
        return $this->getProduct() != null;
    }

    private function escapeString($str)
    {
        return $this->_escaper->escapeHtml(strip_tags($str));
    }
}
