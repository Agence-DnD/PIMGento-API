<?php

/**
 * Class Pimgento_Api_Model_Job_Category
 *
 * @category  Class
 * @package   Pimgento_Api_Model_Job_Category
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Job_Category extends Pimgento_Api_Model_Job_Abstract
{
    /**
     * Category tree maximum depth
     *
     * @var int MAX_DEPTH
     */
    const MAX_DEPTH = 10;
    /**
     * Category entity type id
     *
     * @var int $categoryEntityTypeId
     */
    protected $categoryEntityTypeId;
    /**
     * Default attribute set id
     *
     * @var int $defaultAttributeSetId
     */
    protected $defaultAttributeSetId;

    /**
     * Pimgento_Api_Model_Job_Category constructor
     */
    public function __construct()
    {
        $this->code                       = 'category';
        $this->indexerProcesses           = [
            'catalog_category_flat',
            'catalog_url',
            'catalog_category_product',
        ];
        $this->enterpriseIndexerProcesses = [
            'catalog_url_category',
            'url_redirect',
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
    public function createTable($task)
    {
        /** @var Akeneo\Pim\ApiClient\AkeneoPimClientInterface $client */
        $client = $this->getClient();
        /** @var \Akeneo\Pim\ApiClient\Pagination\PageInterface $categories */
        $categories = $client->getCategoryApi()->listPerPage(1);
        /** @var mixed[] $category */
        $category = $categories->getItems();
        $category = reset($category);
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = $this->getHelper();
        if (empty($category)) {
            $task->stop($helper->__('No results retrieved from Akeneo for %s import', $this->getCode()));
        }

        /** @var Pimgento_Api_Helper_Entities $entitiesHelper */
        $entitiesHelper = Mage::helper('pimgento_api/entities');
        /** @var string[] $columns */
        $columns = $entitiesHelper->getColumnNamesFromResult($category);
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var bool $result */
        $result = $resourceEntities->createTemporaryTable($columns);
        if (!$result) {
            $task->stop($helper->__('Temporary table creation failed for %s import', $this->getCode()));
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
    public function insertData($task)
    {
        /** @var Akeneo\Pim\ApiClient\AkeneoPimClientInterface $client */
        $client = $this->getClient();
        /** @var int $paginationSize */
        $paginationSize = $this->getConfigurationHelper()->getPaginationSize();
        /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface $categories */
        $categories = $client->getCategoryApi()->all($paginationSize);
        /** @var Pimgento_Api_Helper_Entities $entitiesHelper */
        $entitiesHelper = Mage::helper('pimgento_api/entities');
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();

        /**
         * @var int     $index
         * @var mixed[] $category
         */
        foreach ($categories as $index => $category) {
            /** @var string[] $columns */
            $columns = $entitiesHelper->getColumnsFromResult($category);
            /** @var bool $result */
            $result = $resourceEntities->insertDataFromApi($columns);
            if (!$result) {
                $task->stop($this->getHelper()->__('Could not insert Category data in temp table'));
            }
        }
        if (!isset($index)) {
            $task->stop($this->getHelper()->__('No Category data to insert in temp table'));
        }
        $index++;

        $task->setStepMessage($this->getHelper()->__('%d line(s) found', $index));
    }

    /**
     * Match Entity with Code (Step 3)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception|Zend_Db_Statement_Exception
     */
    public function matchEntity($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        $resourceEntities->matchEntity(
            Pimgento_Api_Model_Resource_Entities::PIM_CODE_KEY,
            'catalog/category',
            Mage_Eav_Model_Entity::DEFAULT_ENTITY_ID_FIELD
        );

        $task->setStepMessage($this->getHelper()->__('Entity matching successful'));
    }

    /**
     * Set categories Url Key in temp table (Step 4)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception|Zend_Db_Statement_Exception
     */
    public function prepareUrlKey($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var string $tableName */
        $tableName = $this->getTableName();
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        /** @var Mage_Catalog_Model_Category $categorySingleton */
        $categorySingleton = Mage::getSingleton('catalog/category');
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
                $task->setStepWarning($this->getHelper()->__('Column %s does not exist in temp table', $column));

                continue;
            }

            /** @var string $urlKeyColumn */
            $urlKeyColumn = sprintf('url_key-%s', $local);
            $adapter->addColumn($tableName, $urlKeyColumn, 'VARCHAR(255) NOT NULL DEFAULT ""');

            /** @var Varien_Db_Select $select */
            $select = $adapter->select()->from($tableName, ['entity_id' => '_entity_id', 'name' => $column]);

            /** @var bool $updateUrl */
            $updateUrl = true; //@TODO: retrieve update URL from config
            if (!$updateUrl) {
                $select->where('_is_new = ?', 1);
            }

            /** @var \Zend_Db_Statement_Interface $query */
            $query = $adapter->query($select);

            /** @var string[] $keys */
            $keys = [];
            /** @var string[] $row */
            while (($row = $query->fetch())) {
                /** @var string $urlKey */
                $urlKey = $categorySingleton->formatUrlKey($row['name']);
                /** @var string $finalKey */
                $finalKey = $urlKey;
                /** @var int $increment */
                $increment = 1;

                while (in_array($finalKey, $keys)) {
                    $finalKey = sprintf('%s-%s', $urlKey, $increment++);
                }

                $keys[] = $finalKey;

                $adapter->update(
                    $tableName,
                    [$urlKeyColumn => $finalKey],
                    ['_entity_id = ?' => $row['entity_id']]
                );
            }
        }

        $task->setStepMessage($this->getHelper()->__('Category Url keys generated'));
    }

    /**
     * Set Categories level (Step 5)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    public function setLevel($task)
    {
        /** @var string $tableName */
        $tableName = $this->getTableName();
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);

        $adapter->addColumn($tableName, 'level', 'INT(11) NOT NULL DEFAULT 0');
        $adapter->addColumn($tableName, 'path', 'VARCHAR(255) NOT NULL DEFAULT ""');
        $adapter->addColumn($tableName, 'parent_id', 'INT(11) NOT NULL DEFAULT 0');

        /** @var mixed[] $values */
        $values = ['level' => 1, 'path' => new Zend_Db_Expr('CONCAT(1,"/",`_entity_id`)'), 'parent_id' => 1];
        $adapter->update($tableName, $values, 'parent IS NULL');

        /** @var int $depth */
        $depth = self::MAX_DEPTH;
        /** @var int $i */
        for ($i = 1; $i <= $depth; $i++) {
            $adapter->query(
                'UPDATE `' . $tableName . '` c1
                INNER JOIN `' . $tableName . '` c2 ON c2.`code` = c1.`parent`
                SET c1.`level` = c2.`level` + 1,
                    c1.`path` = CONCAT(c2.`path`,"/",c1.`_entity_id`),
                    c1.`parent_id` = c2.`_entity_id`
                WHERE c1.`level` <= c2.`level` - 1'
            );
        }

        $task->setStepMessage($this->getHelper()->__('Category tree level set'));
    }

    /**
     * Set categories position (Step 6)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception|Zend_Db_Statement_Exception
     */
    public function setPosition($task)
    {
        /** @var string $tableName */
        $tableName = $this->getTableName();
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);

        $adapter->addColumn($tableName, 'position', 'INT(11) NOT NULL DEFAULT 0');

        /** @var Zend_Db_Statement_Interface $query */
        $query = $adapter->query(
            $adapter->select()->from($tableName, ['entity_id' => '_entity_id', 'parent_id' => 'parent_id'])
        );

        while (($row = $query->fetch())) {
            /** @var string $position */
            $position = $adapter->fetchOne(
                $adapter->select()->from($tableName, ['position' => new Zend_Db_Expr('MAX(`position`) + 1')])->where(
                    'parent_id = ?',
                    $row['parent_id']
                )->group('parent_id')
            );

            /** @var string[] $values */
            $values = ['position' => $position];

            $adapter->update($tableName, $values, ['_entity_id = ?' => $row['entity_id']]);
        }

        $task->setStepMessage($this->getHelper()->__('Categories positions set'));
    }

    /**
     * Create category entities (Step 7)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    public function createEntities($task)
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        /** @var Pimgento_Api_Helper_Entities $entitiesHelper */
        $entitiesHelper = Mage::helper('pimgento_api/entities');

        /** @var mixed[] $values */
        $values = [
            'entity_id'        => '_entity_id',
            'entity_type_id'   => new Zend_Db_Expr($entitiesHelper->getCategoryEntityTypeId()),
            'attribute_set_id' => new Zend_Db_Expr($entitiesHelper->getCategoryDefaultAttributeSetId()),
            'parent_id'        => 'parent_id',
            'updated_at'       => new Zend_Db_Expr('now()'),
            'path'             => 'path',
            'position'         => 'position',
            'level'            => 'level',
            'children_count'   => new Zend_Db_Expr('0'),
        ];

        /** @var Varien_Db_Select $parents */
        $parents = $adapter->select()->from($this->getTableName(), $values);
        /** @var string $entityTable */
        $entityTable = $resource->getTableName('catalog/category');
        /** @var string[] $columns */
        $columns = array_keys($values);

        $adapter->query(
            $adapter->insertFromSelect(
                $parents,
                $entityTable,
                $columns,
                Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE
            )
        );

        /** @var mixed[] $values */
        $values = ['created_at' => new Zend_Db_Expr('now()')];
        $adapter->update($entityTable, $values, 'created_at IS NULL');

        $task->setStepMessage($this->getHelper()->__('Category entities created and updated'));
    }

    /**
     * Set values to attributes (Step 8)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    public function setValues($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var string $entityTable */
        $entityTable = 'catalog/category';
        /** @var Pimgento_Api_Helper_Entities $entitiesHelper */
        $entitiesHelper = Mage::helper('pimgento_api/entities');

        /** @var mixed[] $values */
        $values = [
            'is_active'       => new Zend_Db_Expr($this->getConfigurationHelper()->getIsCategoryActive()),
            'include_in_menu' => new Zend_Db_Expr($this->getConfigurationHelper()->getIsCategoryInMenu()),
            'is_anchor'       => new Zend_Db_Expr($this->getConfigurationHelper()->getIsCategoryAnchor()),
            'display_mode'    => new Zend_Db_Expr(sprintf('"%s"', Mage_Catalog_Model_Category::DM_PRODUCT)),
        ];

        $resourceEntities->setValues(
            $this->getCode(),
            $entityTable,
            $values,
            $entitiesHelper->getCategoryEntityTypeId(),
            0,
            Varien_Db_Adapter_Interface::INSERT_IGNORE
        );

        /** @var Pimgento_Api_Helper_Store $storeHelper */
        $storeHelper = Mage::helper('pimgento_api/store');
        /** @var mixed[] $stores */
        $stores = $storeHelper->getStores('lang');

        /**
         * @var string   $local
         * @var string[] $storeValues
         */
        foreach ($stores as $local => $storeValues) {
            /** @var string $column */
            $column = sprintf('labels-%s', $local);
            if (!$resourceEntities->columnExists($this->getTableName(), $column)) {
                $task->setStepWarning($this->getHelper()->__('Column %s does not exist in temp table', $column));

                continue;
            }

            /** @var string $urlKeyColumn */
            $urlKeyColumn = sprintf('url_key-%s', $local);
            /** @var string[] $storeValue */
            foreach ($storeValues as $storeValue) {
                /** @var string[] $values */
                $values = ['name' => $column, 'url_key' => $urlKeyColumn];
                $resourceEntities->setValues(
                    $this->getCode(),
                    $entityTable,
                    $values,
                    $entitiesHelper->getCategoryEntityTypeId(),
                    $storeValue['store_id']
                );
            }
        }

        $task->setStepMessage($this->getHelper()->__('Values set successfully for %s import', $this->getCode()));
    }

    /**
     * Update Children Count (Step 9)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     */
    public function updateChildrenCount($task)
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);

        $adapter->query(
            'UPDATE `' . $resource->getTableName('catalog/category') . '` c
            SET `children_count` = (
                SELECT COUNT(`parent_id`) FROM (
                    SELECT * FROM `' . $resource->getTableName('catalog/category') . '`
                ) tmp
                WHERE tmp.`path` LIKE CONCAT(c.`path`,\'/%\')
            )'
        );

        $task->setStepMessage($this->getHelper()->__('Category children count successful'));
    }

    /**
     * Set Url Keys (Step 10)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception|Zend_Db_Statement_Exception
     */
    public function setUrlKey($task)
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Pimgento_Api_Helper_Entities $entitiesHelper */
        $entitiesHelper = Mage::helper('pimgento_api/entities');
        /** @var string $tableName */
        $tableName = $this->getTableName();
        /** @var Mage_Catalog_Model_Category $categorySingleton */
        $categorySingleton = Mage::getSingleton('catalog/category');
        /** @var Pimgento_Api_Helper_Store $storeHelper */
        $storeHelper = Mage::helper('pimgento_api/store');
        /** @var mixed[] $stores */
        $stores = $storeHelper->getStores('lang');
        /** @var mixed[] $urlAttribute */
        $urlAttribute = $resourceEntities->getAttribute('url_key', $entitiesHelper->getCategoryEntityTypeId());
        /** @var bool $isMagentoEnterprise */
        $isMagentoEnterprise = Mage::getEdition() === Mage::EDITION_ENTERPRISE;

        if ($isMagentoEnterprise) {
            /** @var Mage_Core_Model_Factory $factory */
            $factory = Mage::getSingleton('core/factory');
            /** @var Enterprise_Catalog_Model_Category_Redirect $redirect */
            $redirect = $factory->getModel('enterprise_catalog/category_redirect');
        }

        /**
         * @var string  $local
         * @var mixed[] $ids
         */
        foreach ($stores as $local => $storeValues) {
            /** @var string $column */
            $column = sprintf('labels-%s', $local);
            if (!$resourceEntities->columnExists($tableName, $column)) {
                $task->setStepWarning($this->getHelper()->__('Column %s does not exist in temp table', $column));

                continue;
            }

            /** @var string $urlKeyColumn */
            $urlKeyColumn = sprintf('url_key-%s', $local);
            /** @var string[] $storeValue */
            foreach ($storeValues as $storeValue) {
                /** @var string $select */
                $select = $adapter->select()->from(
                    $tableName,
                    ['_entity_id', 'name' => $column, 'url_key' => $urlKeyColumn]
                );
                /** @var Zend_Db_Statement_Interface $query */
                $query = $adapter->query($select);

                while (($row = $query->fetch())) {
                    if (empty($row['url_key'])) {
                        $task->setStepWarning(
                            $this->getHelper()->__('No url_key set for category %s', $row['_entity_id'])
                        );

                        continue;
                    }

                    /** @var string $newUrlKey */
                    $newUrlKey = $categorySingleton->formatUrlKey($row['url_key']);
                    $newUrlKey = sprintf('"%s"', $newUrlKey);
                    /** @var mixed[] $values */
                    $values = [
                        'entity_type_id' => new Zend_Db_Expr($entitiesHelper->getCategoryEntityTypeId()),
                        'attribute_id'   => new Zend_Db_Expr($urlAttribute['attribute_id']),
                        'store_id'       => new Zend_Db_Expr($storeValue['store_id']),
                        'entity_id'      => new Zend_Db_Expr($row['_entity_id']),
                        'value'          => new Zend_Db_Expr($newUrlKey),
                    ];

                    /** @var string $table */
                    $table = $resourceEntities->getValueTable('catalog/category', $urlAttribute['backend_type']);

                    if (!$isMagentoEnterprise) {
                        $adapter->insertOnDuplicate($table, $values);

                        continue;
                    }

                    // Handle Enterprise Version URLs
                    /** @var string $urlTable */
                    $urlTable = $resourceEntities->getValueTable('catalog/category', 'url_key');

                    /** @var string $currentUrl */
                    $currentUrl = $adapter->fetchOne(
                        $adapter->select()->from($urlTable, ['value'])->where(
                            'entity_id = ?',
                            new Zend_Db_Expr($row['_entity_id'])
                        )->where('attribute_id = ?', new Zend_Db_Expr($urlAttribute['attribute_id']))->where(
                            'store_id = ?',
                            new Zend_Db_Expr($storeValue['store_id'])
                        )->limit(1)
                    );

                    if (empty($currentUrl) || $currentUrl != $newUrlKey) {
                        continue;
                    }

                    $categorySingleton->setId($row['_entity_id']);

                    $adapter->dropTemporaryTable(Enterprise_Catalog_Model_Category_Redirect::TMP_TABLE_NAME);

                    $redirect->saveCustomRedirects($categorySingleton, $storeValue['store_id']);
                }
            }
        }

        $task->setStepMessage($this->getHelper()->__('Category Url set successfully'));
    }

    /**
     * Drop table (Step 11)
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
