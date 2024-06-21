<?php

namespace Kukil\CustomerImport\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Api\AccountManagementInterface;

class ImportData
{

    /**
     * Construct function
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param AccountManagementInterface $accountManagement
     * @param \Magento\Customer\Model\CustomerExtractor $customerExtractor
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        protected \Psr\Log\LoggerInterface $logger,
        protected AccountManagementInterface $accountManagement,
        protected \Magento\Customer\Model\CustomerExtractor $customerExtractor,
        protected \Magento\Framework\App\RequestInterface $request
    ) {
        $this->logger = $logger;
        $this->accountManagement = $accountManagement;
        $this->customerExtractor = $customerExtractor;
        $this->request = $request;
    }

    /**
     * Function to save customer csv file
     *
     * @param array $customerData
     * @return void
     */
    public function save($customerData)
    {
        $this->request->setParams($customerData);
        $customerObject = $this->customerExtractor->extract('customer_account_create', $this->request);
        $customer = $this->accountManagement->createAccount($customerObject);
    }
}
