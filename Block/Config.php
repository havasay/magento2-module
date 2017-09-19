<?php

namespace Havasay\Havasay\Block;

use Magento\Store\Model\ScopeInterface;

class Config
{
    const HAVASAY_TABWIDGET_ENABLED = 'havasay/settings/tabwidget_enabled';
    const HAVASAY_AGGREGATE_ENABLED = 'havasay/settings/aggregatewidget_enabled';
    const HAVASAY_SHAREWIDGET_ENABLED = 'havasay/settings/sharewidget_enabled';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
    }

    public function isTabWidgetEnabled()
    {
        return (bool)$this->_scopeConfig->getValue(self::HAVASAY_TABWIDGET_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    public function isAggregateWidgetEnabled()
    {
        return (bool)$this->_scopeConfig->getValue(self::HAVASAY_AGGREGATE_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    public function isSharewidgetEnabled()
    {
        return (bool)$this->_scopeConfig->getValue(self::HAVASAY_SHAREWIDGET_ENABLED, ScopeInterface::SCOPE_STORE);
    }
}
