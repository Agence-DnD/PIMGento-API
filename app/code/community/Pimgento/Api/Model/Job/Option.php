<?php

/**
 * Class Pimgento_Api_Model_Job_Option
 *
 * @category  Class
 * @package   Pimgento_Api_Model_Job_Option
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Job_Option extends Pimgento_Api_Model_Job_Abstract
{
    /**
     * Pimgento_Api_Model_Job_Family constructor
     */
    public function __construct()
    {
        $this->code             = 'option';
        $this->indexerProcesses = [
            'catalog_product_attribute',
        ];
    }

    /**
     * Create table (Step 1)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Mage_Core_Exception|Pimgento_Api_Exception|Task_Executor_Exception|Zend_Db_Exception
     */
    public function createTable(Task_Executor_Model_Task $task)
    {
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = $this->getHelper();
        /** @var Akeneo\Pim\ApiClient\AkeneoPimClientInterface|Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface $client */
        $client = $this->getClient();
        /** @var Pimgento_Api_Helper_Attribute $attributeHelper */
        $attributeHelper = Mage::helper('pimgento_api/attribute');
        /** @var string[] $selectTypes */
        $selectTypes = $attributeHelper->getAkeneoSelectTypes();
        /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface $attributes */
        $attributes = $client->getAttributeApi()->all();
        /** @var Akeneo\Pim\ApiClient\Api\AttributeOptionApiInterface $optionApi */
        $optionApi = $client->getAttributeOptionApi();

        /** @var mixed[] $optionItem */
        $optionItem = [];
        /** @var mixed[] $attribute */
        foreach ($attributes as $attribute) {
            if (!in_array($attribute['type'], $selectTypes)) {
                continue;
            }

            /** @var Akeneo\Pim\ApiClient\Pagination\PageInterface $option */
            $option = $optionApi->listPerPage($attribute['code'], 1);
            /** @var mixed[] $optionItem */
            $optionItem = $option->getItems();
            if (!empty($optionItem)) {
                $optionItem = reset($optionItem);

                break;
            }
        }

        if (empty($optionItem)) {
            $task->stop($helper->__('No option could be found with all required values.'));
        }

        /** @var Pimgento_Api_Helper_Entities $entitiesHelper */
        $entitiesHelper = Mage::helper('pimgento_api/entities');
        /** @var string[] $columnNames */
        $columnNames = $entitiesHelper->getColumnNamesFromResult($optionItem);

        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var bool $result */
        $result = $resourceEntities->createTemporaryTable($columnNames);
        if (empty($result)) {
            $task->stop($helper->__('Temporary table creation failed'));
        }

        $task->setStepMessage($helper->__('Temporary table created successfully for %s import', $this->getCode()));
    }

    /**
     * Insert data (Step 2)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Mage_Core_Exception|Pimgento_Api_Exception|Task_Executor_Exception
     */
    public function insertData(Task_Executor_Model_Task $task)
    {
        /** @var \Akeneo\Pim\ApiClient\AkeneoPimClientInterface|\Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface $client */
        $client = $this->getClient();
        /** @var int $paginationSize */
        $paginationSize = $this->getConfigurationHelper()->getPaginationSize();
        /** @var Pimgento_Api_Helper_Attribute $attributeHelper */
        $attributeHelper = Mage::helper('pimgento_api/attribute');
        /** @var string[] $selectTypes */
        $selectTypes = $attributeHelper->getAkeneoSelectTypes();
        /** @var bool $isPrefixEnabled */
        $isPrefixEnabled = $this->getConfigurationHelper()->isPrefixEnabled();
        /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface $attributes */
        $attributes = $client->getAttributeApi()->all();

        /** @var int $count */
        $count = 0;
        /**
         * @var int     $index
         * @var mixed[] $attribute
         */
        foreach ($attributes as $index => $attribute) {
            if (empty($attribute['type']) || empty($attribute['code']) || !in_array($attribute['type'], $selectTypes)) {
                continue;
            }

            /** @var bool $isPrefixRequired */
            $isPrefixRequired = false;
            /** @var string $attributeCode */
            $attributeCode = $attribute['code'];
            if ($attributeHelper->isAttributeCodeReserved($attributeCode)) {
                if (!$isPrefixEnabled) {
                    $task->setStepWarning($this->getHelper()->__('Attribute code %s is reserved, options bypassed', $attributeCode));

                    continue;
                }

                $isPrefixRequired = true;
            }

            /** @var int|bool $result */
            $result = $this->insertAttributeOptionData($attributeCode, $paginationSize, $isPrefixRequired);
            if ($result === false) {
                $task->stop($this->getHelper()->__('Could not insert Option data in temp table'));
            }
            $count += $result;
        }
        if ($count === 0) {
            $task->stop($this->getHelper()->__('No Option data to insert in temp table'));
        }

        $task->setStepMessage($this->getHelper()->__('%d line(s) found', $count));
    }

    /**
     * Match Entity with Code (Step 3)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception|Zend_Db_Statement_Exception
     */
    public function matchEntity(Task_Executor_Model_Task $task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        $resourceEntities->matchEntity(
            Pimgento_Api_Model_Resource_Entities::PIM_CODE_KEY,
            'eav/attribute_option',
            'option_id',
            'attribute'
        );

        $task->setStepMessage($this->getHelper()->__('Entity matching successful'));
    }

    /**
     * Insert Option (Step 4)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    public function insertOptions(Task_Executor_Model_Task $task)
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var string $tableName */
        $tableName = $this->getTableName();
        /** @var Pimgento_Api_Model_Job_Attribute $attribute */
        $attribute = Mage::getSingleton('pimgento_api/job_attribute');
        /** @var string $attributeCode */
        $attributeCode = $attribute->getCode();

        /** @var string[] $columns */
        $columns = [
            'option_id'  => 'a._entity_id',
            'sort_order' => new Zend_Db_Expr('"0"'),
        ];

        if ($resourceEntities->columnExists($tableName, 'sort_order')) {
            $columns['sort_order'] = 'a.sort_order';
        }

        /** @var string $condition */
        $condition = sprintf('a.attribute = b.code AND b.import = "%s"', $attributeCode);
        /** @var Varien_Db_Select $options */
        $options = $adapter->select()->from(['a' => $tableName], $columns)->joinInner(
            ['b' => $resourceEntities->getMainTable()],
            new Zend_Db_Expr($condition),
            [
                'attribute_id' => 'b.entity_id',
            ]
        );

        /** @var string $insert */
        $insert = $adapter->insertFromSelect(
            $options,
            $resource->getTableName('eav/attribute_option'),
            [
                'option_id',
                'sort_order',
                'attribute_id',
            ],
            Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE
        );

        $adapter->query($insert);

        $task->setStepMessage($this->getHelper()->__('Options set successfully.'));
    }

    /**
     * Insert Values (Step 5)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    public function insertValues(Task_Executor_Model_Task $task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        /** @var string $tableName */
        $tableName = $this->getTableName();
        /** @var Pimgento_Api_Helper_Store $storeHelper */
        $storeHelper = Mage::helper('pimgento_api/store');
        /** @var mixed[] $stores */
        $stores = $storeHelper->getStores('lang');

        /**
         * @var string  $local
         * @var mixed[] $storeValues
         */
        foreach ($stores as $local => $storeValues) {

            /** @var string $column */
            $column = sprintf('labels-%s', $local);
            if (!$resourceEntities->columnExists($tableName, $column)) {
                $task->setStepWarning($this->getHelper()->__('No labels for %s locale', $local));

                continue;
            }

            /** @var string[] $storeValue */
            foreach ($storeValues as $storeValue) {
                /** @var Varien_Db_Select $options */
                $options = $adapter->select()->from(
                    ['a' => $tableName],
                    [
                        'option_id' => '_entity_id',
                        'store_id'  => new Zend_Db_Expr($storeValue['store_id']),
                        'value'     => $column,
                    ]
                );

                /** @var string $insert */
                $insert = $adapter->insertFromSelect(
                    $options,
                    $resource->getTableName('eav/attribute_option_value'),
                    [
                        'option_id',
                        'store_id',
                        'value',
                    ],
                    Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE
                );

                $adapter->query($insert);
            }
        }

        $task->setStepMessage($this->getHelper()->__('Option Values set successfully.'));
    }

    /**
     * Drop table (Step 6)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    public function dropTable(Task_Executor_Model_Task $task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        $resourceEntities->dropTemporaryTable();

        $task->setStepMessage($this->getHelper()->__('Temporary table drop successful'));
    }

    /**
     * Insert each Attribute Option data in temporary table
     *
     * @param string $attributeCode
     * @param int    $paginationSize
     * @param bool   $isPrefixRequired
     *
     * @return bool|int
     * @throws Mage_Core_Exception|Pimgento_Api_Exception
     */
    protected function insertAttributeOptionData($attributeCode, $paginationSize, $isPrefixRequired = false)
    {
        /** @var Pimgento_Api_Helper_Entities $entitiesHelper */
        $entitiesHelper = Mage::helper('pimgento_api/entities');
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface $attributeOptions */
        $attributeOptions = $this->getClient()->getAttributeOptionApi()->all($attributeCode, $paginationSize);

        /**
         * @var int $index
         * @var mixed[] $variant
         */
        foreach ($attributeOptions as $index => $option) {
            if ($isPrefixRequired === true && isset($option['attribute'])) {
                $option['attribute'] = Pimgento_Api_Helper_Attribute::RESERVED_ATTRIBUTE_CODE_PREFIX . $option['attribute'];
            }
            /** @var string[] $columns */
            $columns = $entitiesHelper->getColumnsFromResult($option);
            /** @var bool $result */
            $result = $resourceEntities->insertDataFromApi($columns);
            if (!$result) {
                return false;
            }
        }

        if (empty($index)) {
            return 0;
        }
        $index++;

        return $index;
    }
}
