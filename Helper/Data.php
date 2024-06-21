<?php

namespace Kukil\CustomerImport\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    /**
     * Construct function
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */

    public function __construct(
        protected \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Function to get Config Data
     *
     * @param string                              $path  path
     * @param \Magento\Store\Model\ScopeInterface $scope scope
     *
     * @return integer|string|bool
     */
    public function getConfigValue($path, $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->scopeConfig->getValue(
            $path,
            $scope,
            $storeId,
        );
    }
}
