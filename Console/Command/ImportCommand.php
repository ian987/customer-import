<?php

namespace Kukil\CustomerImport\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
 
class ImportCommand extends Command
{
    private const FILE_NAME = 'file';
    private const PROFILE_NAME = 'profile';
    private const CSV_TYPE = 'csv';
    private const JSON_TYPE = 'json';
    private const PROFILE_JSON = 'sample-json';
    private const PROFILE_CSV = 'sample-csv';

    public const RETURN_SUCCESS = 0;
    public const RETURN_FAILURE = 1;

    /**
     * Construct function
     *
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param \Magento\Framework\File\Csv $csv
     * @param \Kukil\CustomerImport\Helper\Data $helper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\State $state
     * @param \Kukil\CustomerImport\Model\ImportData $importData
     * @param \Magento\Framework\Filesystem\Io\File $ioFile
     */

    public function __construct(
        protected \Magento\Framework\Filesystem\Driver\File $fileDriver,
        protected \Magento\Framework\File\Csv $csv,
        protected \Kukil\CustomerImport\Helper\Data $helper,
        protected \Psr\Log\LoggerInterface $logger,
        protected \Magento\Framework\App\State $state,
        protected \Kukil\CustomerImport\Model\ImportData $importData,
        protected \Magento\Framework\Filesystem\Io\File $ioFile
    ) {
        $this->fileDriver = $fileDriver;
        $this->csv = $csv;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->state = $state;
        $this->importData = $importData;
        $this->ioFile = $ioFile;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName('customer:import')
            ->setDescription('Import customer from command line!');

        $this->addArgument(
            self::PROFILE_NAME,
            InputArgument::REQUIRED
        );
        $this->addArgument(
            self::FILE_NAME,
            InputArgument::REQUIRED
        );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exitCode = 0;
        $count = 0;

        $profile = $input->getArgument(self::PROFILE_NAME);
        $fileName = $input->getArgument(self::FILE_NAME);
        $fileInfo = $this->ioFile->getPathInfo($fileName, PATHINFO_EXTENSION);
        $extension = $fileInfo['extension'];

        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);

        try {
            if ($this->fileDriver->isExists($fileName)) {
                if ($extension == self::CSV_TYPE && stripos($profile, self::CSV_TYPE) !== false) {
                    
                    $this->csv->setDelimiter(",");
                    $csvData = $this->csv->getData($fileName);

                    $csvHeaders = array_shift($csvData);

                    foreach ($csvData as $key => $value) {
                        if (count($csvHeaders) !== count($value)) {
                            $output->writeln('The CSV file data is incorrect!');
                            return self::RETURN_FAILURE;
                        }
                        $csvData[$key] = array_combine($csvHeaders, $value);
                    }

                    foreach ($csvData as $record) {
                        $groupId = $this->helper
                            ->getConfigValue(\Magento\Customer\Model\GroupManagement::XML_PATH_DEFAULT_ID);
       
                        $customerData = [
                            CustomerInterface::FIRSTNAME => $record['fname'] ?? "",
                            CustomerInterface::LASTNAME => $record['lname'] ?? "",
                            CustomerInterface::EMAIL => $record['emailaddress'] ?? "",
                            CustomerInterface::GROUP_ID  => $groupId
                        ];
                        
                        $this->importData->save($customerData);

                        $count++;
                    }
                    
                } elseif ($extension == self::JSON_TYPE && stripos($profile, self::JSON_TYPE) !== false) {
                    $jsonFile = $this->fileDriver->fileGetContents($fileName);
           
                    // Decode the JSON file
                    $jsonData = json_decode($jsonFile, true);
                
                    foreach ($jsonData as $key => $value) {
                    
                        $groupId = $this->helper
                            ->getConfigValue(\Magento\Customer\Model\GroupManagement::XML_PATH_DEFAULT_ID);
       
                        $customerData = [
                            CustomerInterface::FIRSTNAME => $value['fname'] ?? "",
                            CustomerInterface::LASTNAME => $value['lname'] ?? "",
                            CustomerInterface::EMAIL => $value['emailaddress'] ?? "",
                            CustomerInterface::GROUP_ID  => $groupId
                        ];

                        $this->importData->save($customerData);

                        $count++;
                    }
                } else {
                    $output->writeln('The provided profile and extension doesnot match');
                    return self::RETURN_FAILURE;
                }

                $output->writeln(sprintf(
                    '<info>A total of %s records has been inserted</info>',
                    $count
                ));

                return self::RETURN_SUCCESS;

            } else {
                $output->writeln('The provided file doesnot exist');
            }
        } catch (LocalizedException $e) {
            $this->logger->info($e->getMessage());

            $output->writeln(sprintf(
                '<error>%s</error>',
                $e->getMessage()
            ));

            return self::RETURN_FAILURE;
            
        }
    }
}
