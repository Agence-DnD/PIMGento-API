<?php

/**
 * Class Pimgento_Api_Model_Job_Product_Model
 *
 * @category  Class
 * @package   Pimgento_Api_Model_Job_Product_Model
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Job_Product_Model extends Pimgento_Api_Model_Job_Abstract
{
    /**
     * Maximum data handled in batch
     *
     * @var int $batchSize
     */
    private $batchSize = 500;
    /**
     * Columns we do not need to create in main table from temp table
     *
     * @var string[] $excluded
     */
    private $excluded = [
        'type',
        '_entity_id',
        '_is_new',
    ];
    /**
     * Column to preserve from deletion
     *
     * @var string[] $preserved
     */
    private $preserved = [
        'code',
        'axes',
    ];

    /**
     * Pimgento_Api_Model_Job_Product_Model constructor
     */
    public function __construct()
    {
        $this->code = 'product_model';
    }

    /**
     * Get maximum batch size
     *
     * @return int
     */
    public function getBatchSize()
    {
        return $this->batchSize;
    }

    /**
     * Get excluded columns
     *
     * @return string[]
     */
    public function getExcluded()
    {
        return $this->excluded;
    }

    /**
     * Get columns to preserve
     *
     * @return string[]
     */
    public function getPreserved()
    {
        return $this->preserved;
    }

    /**
     * Create temporary table from Api results
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Mage_Core_Exception|Pimgento_Api_Exception|Task_Executor_Exception|Zend_Db_Exception
     */
    public function createTable($task)
    {
        /** @var \Akeneo\Pim\ApiClient\AkeneoPimClientInterface|\Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface $client */
        $client = $this->getClient();
        /** @var \Akeneo\Pim\ApiClient\Pagination\PageInterface $productModelCollection */
        $productModelCollection = $client->getProductModelApi()->listPerPage(1);
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = $this->getHelper();
        if ($productModelCollection->getCount() === 0) {
            $task->stop($helper->__('No results retrieved from Akeneo'));
        }
        /** @var mixed[] $productModelItems */
        $productModelItems = $productModelCollection->getItems();
        /** @var mixed[] $productModel */
        $productModel = reset($productModelItems);
        /** @var Pimgento_Api_Helper_Product $productHelper */
        $productHelper = Mage::helper('pimgento_api/product');
        /** @var string[] $columns */
        $columns = $productHelper->getColumnNamesFromResult($productModel);

        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var bool $result */
        $result = $resourceEntities->createTemporaryTable($columns);
        if (!$result) {
            $task->stop($helper->__('Temporary table creation failed'));
        }

        $task->setStepMessage($helper->__('Temporary table created successfully'));
    }

    /**
     * Insert Api response data into temporary table
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Mage_Core_Exception|Pimgento_Api_Exception|Task_Executor_Exception
     */
    public function insertData($task)
    {
        /** @var \Akeneo\Pim\ApiClient\AkeneoPimClientInterface|\Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface $client */
        $client = $this->getClient();
        /** @var int $paginationSize */
        $paginationSize = $this->getConfigurationHelper()->getPaginationSize();
        /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursor $productModelItems */
        $productModelItems = $client->getProductModelApi()->all($paginationSize);
        /** @var Pimgento_Api_Helper_Product $productHelper */
        $productHelper = Mage::helper('pimgento_api/product');
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();

        /** @var int $count */
        $count = 0;
        /** @var mixed[] $productModel */
        foreach ($productModelItems as $index => $productModel) {
            /** @var string[] $columns */
            $columns = $productHelper->getColumnsFromResult($productModel);
            $resourceEntities->insertDataFromApi($columns);
            $count++;
        }
        if (!isset($index)) {
            $task->stop($this->getHelper()->__('Could not insert Product Models data in temp table'));
        }

        $task->setStepMessage($this->getHelper()->__('%d line(s) found', $count));
    }

    /**
     * Remove columns from product model table
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     */
    public function removeColumns($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var string $productModelTable */
        $productModelTable = $resourceEntities->getTable('pimgento_api/product_model');
        /** @var string[] $columns */
        $columns = array_keys($connection->describeTable($productModelTable));
        /** @var string $column */
        foreach ($columns as $column) {
            if (!in_array($column, $this->getPreserved())) {
                $connection->dropColumn($productModelTable, $column);
            }
        }

        $task->setStepMessage($this->getHelper()->__('Columns dropped successfully'));
    }

    /**
     * Add columns to product model table
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    public function addColumns($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var string $tmpTable */
        $tmpTable = $resourceEntities->getTableName();
        /** @var string $productModelTable */
        $productModelTable = $resourceEntities->getTable('pimgento_api/product_model');
        /** @var string[] $columns */
        $columns = array_keys($connection->describeTable($tmpTable));
        /** @var string $column */
        foreach ($columns as $column) {
            if (!in_array($column, $this->getExcluded())) {
                $connection->addColumn($productModelTable, $column, 'TEXT');
            }
        }

        $task->setStepMessage($this->getHelper()->__('Columns successfully created'));
    }

    /**
     * Add or update data in product model table
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    public function updateData($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var string $tmpTable */
        $tmpTable = $resourceEntities->getTableName();
        /** @var string $productModelTable */
        $productModelTable = $resourceEntities->getTable('pimgento_api/product_model');

        /** @var string[] $columns */
        $columns = array_keys($connection->describeTable($tmpTable));
        $columns = array_diff($columns, $this->getExcluded());
        /** @var Varien_Db_Select $select */
        $select = $connection->select()->from($tmpTable, $columns);
        /** @var string $query */
        $query = $connection->insertFromSelect(
            $select,
            $productModelTable,
            $columns,
            Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE
        );
        $connection->query($query);

        $task->setStepMessage($this->getHelper()->__('Product Model table updated successfully.'));
    }

    /**
     * Drop temporary table
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    public function dropTable($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        $resourceEntities->dropTemporaryTable();

        $task->setStepMessage($this->getHelper()->__('Temporary table drop successful'));
    }
}
