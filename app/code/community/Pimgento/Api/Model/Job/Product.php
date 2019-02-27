<?php

/**
 * Class Pimgento_Api_Model_Job_Product
 *
 * @category  Class
 * @package   Pimgento_Api_Model_Job_Product
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Job_Product extends Pimgento_Api_Model_Job_Abstract
{
    /**
     * Disabled status value in PIM
     *
     * @var string $pimProductStatusDisabled
     */
    protected $pimProductStatusDisabled = 0;
    /**
     * Maximum configurable product per insert in DB
     *
     * @var int $maxConfigurableInsertion
     */
    protected $maxConfigurableInsertion = 500;
    /**
     * Akeneo default association types, reformatted as column names
     *
     * @var string[] $associationTypes
     */
    protected $associationTypes = [
        Mage_Catalog_Model_Product_Link::LINK_TYPE_RELATED   => [
            'SUBSTITUTION-products',
            'SUBSTITUTION-product_models',
        ],
        Mage_Catalog_Model_Product_Link::LINK_TYPE_UPSELL    => [
            'UPSELL-products',
            'UPSELL-product_models',
        ],
        Mage_Catalog_Model_Product_Link::LINK_TYPE_CROSSSELL => [
            'X_SELL-products',
            'X_SELL-product_models',
        ],
    ];
    /**
     * List of allowed type_id that can be imported
     *
     * @var string[]
     */
    protected $allowedTypeId = ['simple', 'virtual'];
    /**
     * List of column to exclude from attribute value setting
     *
     * @var string[]
     */
    protected $excludedColumns = [
        '_entity_id',
        '_is_new',
        '_status',
        '_type_id',
        '_options_container',
        '_tax_class_id',
        '_attribute_set_id',
        '_visibility',
        '_children',
        '_axes',
        'code',
        'sku',
        'categories',
        'family',
        'groups',
        'parent',
        'enabled',
        'created',
        'updated',
        'associations',
        'PACK',
        'SUBSTITUTION',
        'UPSELL',
        'X_SELL',
    ];

    /**
     * Pimgento_Api_Model_Job_Product constructor
     */
    public function __construct()
    {
        $this->code = 'product';
    }

    /**
     * Create table
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Mage_Core_Exception|Pimgento_Api_Exception|Task_Executor_Exception|Zend_Db_Exception
     */
    public function createTable($task)
    {
        /** @var Akeneo\Pim\ApiClient\AkeneoPimClientInterface|Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface $client */
        $client = $this->getClient();
        /** @var \Akeneo\Pim\ApiClient\Pagination\PageInterface $products */
        $products = $client->getProductApi()->listPerPage(1);
        /** @var mixed[] $product */
        $product = $products->getItems();
        $product = reset($product);
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = $this->getHelper();
        if (empty($product)) {
            $task->stop($helper->__('No results retrieved from Akeneo for %s import', $this->code));
        }

        /** @var Pimgento_Api_Helper_Entities $productHelper */
        $productHelper = Mage::helper('pimgento_api/product');
        /** @var string[] $columns */
        $columns = $productHelper->getColumnNamesFromResult($product);

        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var bool $result */
        $result = $resourceEntities->createTemporaryTable($columns);
        if (empty($result)) {
            $task->stop($helper->__('Temporary table creation failed'));
        }

        $task->setStepMessage($helper->__('Temporary table created successfully for %s import', $this->getCode()));
    }

    /**
     * Insert data
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Mage_Core_Exception|Pimgento_Api_Exception|Task_Executor_Exception
     */
    public function insertData($task)
    {
        /** @var Pimgento_Api_Helper_Filter_Product $filters */
        $filters = Mage::helper('pimgento_api/filter_product');
        /** @var Akeneo\Pim\ApiClient\AkeneoPimClientInterface|Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface $client */
        $client = $this->getClient();
        /** @var int $paginationSize */
        $paginationSize = $this->getConfigurationHelper()->getPaginationSize();
        /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface $products */
        $products = $client->getProductApi()->all($paginationSize, $filters->getFilters());
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Pimgento_Api_Helper_Product $productHelper */
        $productHelper = Mage::helper('pimgento_api/product');

        /** @var int $index */
        $index = 0;
        /**
         * @var int     $index
         * @var mixed[] $product
         */
        foreach ($products as $index => $product) {
            /** @var string[] $columns */
            $columns = $productHelper->getColumnsFromResult($product);
            /** @var bool $result */
            $result = $resourceEntities->insertDataFromApi($columns);
            if (!$result) {
                $task->stop($this->getHelper()->__('Could not insert Product data in temp table'));
            }
        }
        if (!isset($index)) {
            $task->stop($this->getHelper()->__('No Product data to insert in temp table'));
        }
        if ($index) {
            $index++;
        }

        $task->setStepMessage($this->getHelper()->__('%d line(s) found', $index));
    }

    /**
     * Update Column
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Mage_Core_Exception|Pimgento_Api_Exception|Task_Executor_Exception
     */
    public function updateColumns($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var string $tmpTable */
        $tmpTable = $resourceEntities->getTableName();
        /** @var string $identifier */
        $identifier = 'identifier';
        /** @var Pimgento_Api_Helper_Configuration $configHelper */
        $configHelper = $this->getConfigurationHelper();
        /** @var Pimgento_Api_Helper_Store $storeHelper */
        $storeHelper = Mage::helper('pimgento_api/store');

        /** @var mixed[] $mapping */
        $mapping = $configHelper->getProductAttributeMapping();
        /**
         * @var string   $match
         * @var string[] $match
         */
        foreach ($mapping as $attribute => $match) {
            if ((in_array('identifier', $match))) {
                $identifier = $attribute;
            }
        }
        if (!$connection->tableColumnExists($tmpTable, $identifier)) {
            $task->stop($this->getHelper()->__('Column %s not found', $identifier));
        }

        $connection->changeColumn($tmpTable, $identifier, 'code', 'VARCHAR(255)');

        if (!$connection->tableColumnExists($tmpTable, 'sku')) {
            $connection->addColumn($tmpTable, 'sku', 'VARCHAR(255) NOT NULL default ""');
            $values = [
                'sku' => new Zend_Db_Expr('`code`'),
            ];
            $connection->update($tmpTable, $values);
        }

        $defaultTax = $configHelper->getProductTaxId();

        $connection->addColumn($tmpTable, '_tax_class_id', sprintf('INT(11) NOT NULL default "%s"', $defaultTax));
        $connection->addColumn($tmpTable, '_type_id', 'VARCHAR(255) NOT NULL default "simple"');
        $connection->addColumn($tmpTable, '_options_container', 'VARCHAR(255) NOT NULL default "container2"');
        $connection->addColumn(
            $tmpTable,
            '_attribute_set_id',
            sprintf('INT(11) NOT NULL DEFAULT "%s"', $this->getProductDefaultAttributeSetId())
        );
        $connection->addColumn(
            $tmpTable,
            '_visibility',
            sprintf('INT(11) NOT NULL DEFAULT "%s"', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
        );
        $connection->addColumn(
            $tmpTable,
            '_status',
            sprintf('INT(11) NOT NULL DEFAULT "%s"', Mage_Catalog_Model_Product_Status::STATUS_DISABLED)
        );

        if (!$connection->tableColumnExists($tmpTable, 'url_key')) {
            $connection->addColumn($tmpTable, 'url_key', 'varchar(255) NOT NULL DEFAULT ""');
            $connection->update($tmpTable, ['url_key' => new Zend_Db_Expr('LOWER(`sku`)')]);
        }

        if ($connection->tableColumnExists($tmpTable, 'enabled')) {
            $connection->update($tmpTable, ['_status' => new Zend_Db_Expr('IF(`enabled` <> 1, 2, 1)')]);
        }

        /** @var string|null $groupColumn */
        $groupColumn = null;
        if ($connection->tableColumnExists($tmpTable, 'parent')) {
            $groupColumn = 'parent';
        }
        if ($connection->tableColumnExists($tmpTable, 'groups') && !$groupColumn) {
            $groupColumn = 'groups';
        }

        if ($groupColumn) {
            $connection->update(
                $tmpTable,
                [
                    '_visibility' => new Zend_Db_Expr(
                        sprintf(
                            'IF(`%s` <> "", %s, %s)',
                            $groupColumn,
                            Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
                            Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH
                        )
                    ),
                ]
            );
        }

        if ($connection->tableColumnExists($tmpTable, 'type_id')) {
            /** @var string $types */
            $types = $connection->quote($this->getAllowedTypeId());
            $connection->update(
                $tmpTable,
                [
                    '_type_id' => new Zend_Db_Expr(sprintf("IF(`type_id` IN (%s), `type_id`, 'simple')", $types)),
                ]
            );
        }

        /** @var mixed[] $mapping */
        $mapping = $configHelper->getProductAttributeMapping();
        /** @var mixed[] $stores */
        $stores = $storeHelper->getAllStores();
        /**
         * @var string   $match
         * @var string[] $match
         */
        foreach ($mapping as $pimAttribute => $match) {
            /** @var string $magentoAttribute */
            foreach ($match as $magentoAttribute) {
                if ($pimAttribute === $identifier) {
                    continue;
                }

                $resourceEntities->copyColumn($tmpTable, $pimAttribute, $magentoAttribute);

                /**
                 * @var string $local
                 * @var string $affected
                 */
                foreach ($stores as $local => $affected) {
                    $resourceEntities->copyColumn(
                        $tmpTable,
                        sprintf('%s-%s', $pimAttribute, $local),
                        sprintf('%s-%s', $magentoAttribute, $local)
                    );
                }
            }
        }
    }

    /**
     * Create Configurable
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Mage_Core_Exception|Pimgento_Api_Exception|Task_Executor_Exception
     */
    public function createConfigurable($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var string $tmpTable */
        $tmpTable = $resourceEntities->getTableName();
        /** @var Pimgento_Api_Helper_Configuration $configHelper */
        $configHelper = $this->getConfigurationHelper();
        /** @var Pimgento_Api_Helper_Store $storeHelper */
        $storeHelper = Mage::helper('pimgento_api/store');

        /** @var string|null $groupColumn */
        $groupColumn = null;
        if ($connection->tableColumnExists($tmpTable, 'parent')) {
            $groupColumn = 'parent';
        }
        if (!$groupColumn && $connection->tableColumnExists($tmpTable, 'groups')) {
            $task->setStepWarning(
                $this->getHelper()->__('Column parent not found, trying to find groups column instead.')
            );
            $groupColumn = 'groups';
        }
        if (!$groupColumn) {
            $task->stop($this->getHelper()->__('Column groups not found.'));
        }

        $connection->addColumn($tmpTable, '_children', 'TEXT NULL');
        $connection->addColumn($tmpTable, '_axes', 'VARCHAR(255) NULL');

        /** @var string $productModelTable */
        $productModelTable = $resourceEntities->getTable('pimgento_api/product_model');
        if ($connection->tableColumnExists($productModelTable, 'parent')) {
            /** @var  $select */
            $select = $connection->select()->from(false, [$groupColumn => 'v.parent'])->joinInner(
                ['v' => $productModelTable],
                sprintf('v.parent IS NOT NULL AND e.%s = v.code', $groupColumn),
                []
            );
            $connection->query(
                $connection->updateFromSelect($select, ['e' => $tmpTable])
            );
        }

        $groupColumn = sprintf('e.%s', $groupColumn);
        /** @var mixed[] $data */
        $data = [
            'code'               => $groupColumn,
            'sku'                => $groupColumn,
            'url_key'            => $groupColumn,
            '_children'          => new Zend_Db_Expr('GROUP_CONCAT(e.code SEPARATOR ",")'),
            '_type_id'           => new Zend_Db_Expr('"configurable"'),
            '_options_container' => new Zend_Db_Expr('"container1"'),
            '_status'            => 'e._status',
            '_axes'              => 'v.axes',
        ];

        if ($connection->tableColumnExists($tmpTable, 'family')) {
            $data['family'] = 'e.family';
        }

        if ($connection->tableColumnExists($tmpTable, 'categories')) {
            $data['categories'] = 'e.categories';
        }

        /** @var string[] $associationNames */
        foreach ($this->associationTypes as $associationNames) {
            if (empty($associationNames)) {
                continue;
            }
            /** @var string $associationName */
            foreach ($associationNames as $associationName) {
                if (!empty($associationName) && $connection->tableColumnExists($productModelTable, $associationName) && $connection->tableColumnExists($tmpTable, $associationName)) {
                    $data[$associationName] = sprintf('v.%s', $associationName);
                }
            }
        }

        /** @var mixed[] $stores */
        $stores = $storeHelper->getAllStores();
        /** @var mixed[] $additional */
        $additional = $configHelper->getProductConfigurableAttributes();
        /** @var string[] $attribute */
        foreach ($additional as $attribute) {
            if (!isset($attribute['attribute'], $attribute['value'])) {
                continue;
            }

            /** @var string $name */
            $name = $attribute['attribute'];
            /** @var string $value */
            $value = $attribute['value'];
            /** @var string[] $columns */
            $columns = [trim($name)];

            /**
             * @var string $local
             * @var string $affected
             */
            foreach ($stores as $local => $affected) {
                $columns[] = sprintf('%s-%s', trim($name), $local);
            }

            /** @var string $column */
            foreach ($columns as $column) {
                if ($column === 'enabled' && $connection->tableColumnExists($tmpTable, 'enabled')) {
                    $column = '_status';
                    if ($value === $this->pimProductStatusDisabled) {
                        $value = Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
                    }
                }

                if (!$connection->tableColumnExists($tmpTable, $column)) {
                    continue;
                }

                if (strlen($value) > 0) {
                    $data[$column] = new Zend_Db_Expr(sprintf('"%s"', $value));

                    continue;
                }

                $data[$column] = sprintf('e.%s', $column);
                if ($connection->tableColumnExists($productModelTable, $column)) {
                    $data[$column] = sprintf('v.%s', $column);
                }
            }
        }

        /** @var Varien_Db_Select $configurable */
        $configurable = $connection->select()->from(['e' => $tmpTable], $data)->joinInner(
            ['v' => $productModelTable],
            sprintf('%s = v.code', $groupColumn),
            []
        )->where(sprintf('%s <> ""', $groupColumn))->group($groupColumn);

        /** @var string $query */
        $query = $connection->insertFromSelect($configurable, $tmpTable, array_keys($data));

        $connection->query($query);
    }

    /**
     * Match Entity with Code
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception|Task_Executor_Exception|Zend_Db_Statement_Exception
     */
    public function matchEntity($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var string $tmpTable */
        $tmpTable = $resourceEntities->getTableName();

        /** @var Varien_Db_Select $select */
        $select = $connection->select()->from($tmpTable, ['code'])->group('code')->having('COUNT(code) > ?', 1);
        /** @var string[] $duplicates */
        $duplicates = $connection->fetchCol($select);
        if (!empty($duplicates)) {
            /** @var string $duplicates */
            $duplicates = implode(', ', $duplicates);
            $task->stop($this->getHelper()->__('Duplicates sku detected. Make sure Product Model code is not used for a simple product sku. Duplicates: %1', $duplicates));
        }

        $resourceEntities->matchEntity(Pimgento_Api_Model_Resource_Entities::PIM_CODE_KEY, 'catalog/product', Mage_Eav_Model_Entity::DEFAULT_ENTITY_ID_FIELD);

        $task->setStepMessage($this->getHelper()->__('Entity matching successful'));
    }

    /**
     * Update product attribute set id
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Mage_Core_Exception|Pimgento_Api_Exception|Task_Executor_Exception
     */
    public function updateAttributeSetId($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var string $tmpTable */
        $tmpTable = $resourceEntities->getTableName();

        if (!$connection->tableColumnExists($tmpTable, 'family')) {
            $task->stop($this->getHelper()->__('Column family is missing'));
        }

        /** @var string $entitiesTable */
        $entitiesTable = $resourceEntities->getTable('pimgento_api/entities');
        /** @var Varien_Db_Select $families */
        $families = $connection->select()->from(false, ['_attribute_set_id' => 'c.entity_id'])->joinLeft(
            ['c' => $entitiesTable],
            'p.family = c.code AND c.import = "family"',
            []
        );

        $connection->query($connection->updateFromSelect($families, ['p' => $tmpTable]));

        /** @var int $noFamily */
        $noFamily = (int)$connection->fetchOne(
            $connection->select()->from($tmpTable, ['COUNT(*)'])->where('_attribute_set_id = ?', 0)
        );
        if ($noFamily) {
            $task->setStepWarning(
                $this->getHelper()->__('%s product(s) with default family. Please try to import families.', $noFamily)
            );
        }

        $connection->update(
            $tmpTable,
            ['_attribute_set_id' => $this->getProductDefaultAttributeSetId()],
            ['_attribute_set_id = ?' => 0]
        );
    }

    /**
     * Replace option code by id
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception|Zend_Db_Statement_Exception
     */
    public function updateOption($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var string $tmpTable */
        $tmpTable = $resourceEntities->getTableName();
        /** @var string[] $columns */
        $columns = array_keys($connection->describeTable($tmpTable));
        /** @var string[] $except */
        $except = [
            'url_key',
        ];
        $except = array_merge($except, $this->getExcludedColumns());

        /** @var string $column */
        foreach ($columns as $column) {
            if (in_array($column, $except) || preg_match('/-unit/', $column)) {
                continue;
            }

            if (!$connection->tableColumnExists($tmpTable, $column)) {
                continue;
            }

            /** @var string[] $columnParts */
            $columnParts = explode('-', $column, 2);
            /** @var string $columnPrefix */
            $columnPrefix = reset($columnParts);
            $columnPrefix = sprintf('%s_', $columnPrefix);
            /** @var int $prefixLength */
            $prefixLength = strlen($columnPrefix) + 1;
            /** @var string $entitiesTable */
            $entitiesTable = $resourceEntities->getTable('pimgento_api/entities');

            // Sub select to increase performance versus FIND_IN_SET
            /** @var Varien_Db_Select $subSelect */
            $subSelect = $connection->select()->from(
                ['c' => $entitiesTable],
                ['code' => sprintf('SUBSTRING(`c`.`code`, %s)', $prefixLength), 'entity_id' => 'c.entity_id']
            )->where(sprintf('c.code LIKE "%s%s"', $columnPrefix, '%'))->where('c.import = ?', 'option');

            // if no option no need to continue process
            if (!$connection->query($subSelect)->rowCount()) {
                continue;
            }

            // in case of multiselect
            /** @var string $conditionJoin */
            $conditionJoin = "IF(locate(',', `" . $column . "`) > 0 , " . "`p`.`" . $column . "` LIKE " . new Zend_Db_Expr(
                    "CONCAT('%', `c1`.`code`, '%')"
                ) . ", `p`.`" . $column . "` = `c1`.`code` )";

            /** @var Varien_Db_Select $select */
            $select = $connection->select()->from(
                ['p' => $tmpTable],
                ['code' => 'p.code', 'entity_id' => 'p._entity_id']
            )->joinInner(
                ['c1' => new Zend_Db_Expr('(' . (string)$subSelect . ')')],
                new Zend_Db_Expr($conditionJoin),
                [$column => new Zend_Db_Expr('GROUP_CONCAT(`c1`.`entity_id` SEPARATOR ",")')]
            )->group('p.code');

            /** @var string $query */
            $query = $connection->insertFromSelect(
                $select,
                $tmpTable,
                ['code', '_entity_id', $column],
                Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE
            );

            $connection->query($query);
        }
    }

    /**
     * Create product entities
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    public function createEntities($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var string $tmpTable */
        $tmpTable = $resourceEntities->getTableName();
        /** @var string $table */
        $table = $resourceEntities->getTable('catalog/product');

        /** @var mixed[] $values */
        $values = [
            'entity_id'        => '_entity_id',
            'entity_type_id'   => new Zend_Db_Expr($this->getProductEntityTypeId()),
            'attribute_set_id' => '_attribute_set_id',
            'type_id'          => '_type_id',
            'sku'              => 'sku',
            'has_options'      => new Zend_Db_Expr(0),
            'required_options' => new Zend_Db_Expr(0),
            'updated_at'       => new Zend_Db_Expr('now()'),
        ];

        /** @var Varien_Db_Select $parents */
        $parents = $connection->select()->from($tmpTable, $values);
        /** @var string $query */
        $query = $connection->insertFromSelect(
            $parents,
            $table,
            array_keys($values),
            Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE
        );
        $connection->query($query);

        $values = ['created_at' => new Zend_Db_Expr('now()')];
        $connection->update($table, $values, 'created_at IS NULL');
    }

    /**
     * Set values to attributes
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
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var string $tmpTable */
        $tmpTable = $resourceEntities->getTableName();
        /** @var string[] $attributeScopeMapping */
        $attributeScopeMapping = $resourceEntities->getAttributeScopeMapping();
        /** @var Pimgento_Api_Helper_Store $storeHelper */
        $storeHelper = Mage::helper('pimgento_api/store');
        /** @var mixed[] $stores */
        $stores = $storeHelper->getAllStores();
        /** @var string[] $columns */
        $columns = array_keys($connection->describeTable($tmpTable));
        /** @var string $adminBaseCurrency */
        $adminBaseCurrency = Mage::app()->getBaseCurrencyCode();
        /** @var mixed[] $values */
        $values = [
            0 => [
                'options_container' => '_options_container',
                'tax_class_id'      => '_tax_class_id',
                'visibility'        => '_visibility',
            ],
        ];

        if ($connection->tableColumnExists($tmpTable, 'enabled')) {
            $values[0]['status'] = '_status';
        }

        /** @var string $column */
        foreach ($columns as $column) {
            /** @var string[] $columnParts */
            $columnParts = explode('-', $column, 2);
            /** @var string $columnPrefix */
            $columnPrefix = $columnParts[0];

            if (in_array($columnPrefix, $this->getExcludedColumns()) || preg_match('/-unit/', $column)) {
                continue;
            }

            if (!isset($attributeScopeMapping[$columnPrefix])) {
                // If no scope is found, attribute does not exist
                $task->setStepWarning($this->getHelper()->__('Attribute %s was not found. Please try re-importing attributes.', $columnPrefix));

                continue;
            }

            if (empty($columnParts[1])) {
                // No channel and no locale found: attribute scope naturally is Global
                $values[0][$columnPrefix] = $column;

                continue;
            }

            /** @var int $scope */
            $scope = (int)$attributeScopeMapping[$columnPrefix];
            if ($scope === Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL && !empty($columnParts[1]) && $columnParts[1] === $adminBaseCurrency) {
                // This attribute has global scope with a suffix: it is a price with its currency
                // Only set this Price value if currency matches default Magento currency
                // If Price scope is set to Website, it will be processed afterwards as any website scoped attribute
                $values[0][$columnPrefix] = $column;

                continue;
            }

            /** @var string $columnSuffix */
            $columnSuffix = $columnParts[1];
            if (!isset($stores[$columnSuffix])) {
                // No corresponding store found for this suffix
                $task->setStepWarning($this->getHelper()->__('Column %s was ignored and passed.', $column));

                continue;
            }

            /** @var mixed[] $affectedStores */
            $affectedStores = $stores[$columnSuffix];
            /** @var mixed[] $store */
            foreach ($affectedStores as $store) {
                // Handle website scope
                if ($scope === Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE && !$store['is_website_default']) {
                    continue;
                }

                if ($scope === Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE || empty($store['siblings'])) {
                    $values[$store['store_id']][$columnPrefix] = $column;

                    continue;
                }

                /** @var string[] $siblings */
                $siblings = $store['siblings'];
                /** @var string $storeId */
                foreach ($siblings as $storeId) {
                    $values[$storeId][$columnPrefix] = $column;
                }
            }
        }

        /**
         * @var string  $storeId
         * @var mixed[] $data
         */
        foreach ($values as $storeId => $data) {
            $resourceEntities->setValues(
                $this->getCode(),
                'catalog/product',
                $data,
                $this->getProductEntityTypeId(),
                $storeId,
                Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE
            );
        }
    }

    /**
     * Link configurable with children
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Mage_Core_Exception|Pimgento_Api_Exception|Task_Executor_Exception|Zend_Db_Statement_Exception
     */
    public function linkConfigurable($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var string $tmpTable */
        $tmpTable = $resourceEntities->getTableName();
        /** @var Pimgento_Api_Helper_Store $storeHelper */
        $storeHelper = Mage::helper('pimgento_api/store');

        /** @var string|null $groupColumn */
        $groupColumn = null;
        if ($connection->tableColumnExists($tmpTable, 'parent')) {
            $groupColumn = 'parent';
        }
        if (!$groupColumn && $connection->tableColumnExists($tmpTable, 'groups')) {
            $task->setStepWarning($this->getHelper()->__('Column parent not found, trying to find groups column instead.'));
            $groupColumn = 'groups';
        }
        if (!$groupColumn) {
            $task->stop($this->getHelper()->__('Column groups not found.'));
        }

        /** @var Varien_Db_Select $configurableSelect */
        $configurableSelect = $connection->select()->from($tmpTable, ['_entity_id', '_axes', '_children'])->where(
            '_type_id = ?',
            'configurable'
        )->where('_axes IS NOT NULL')->where('_children IS NOT NULL');

        /** @var mixed[] $valuesLabels */
        $valuesLabels = [];
        /** @var mixed[] $valuesRelations */
        $valuesRelations = []; // catalog_product_relation
        /** @var mixed[] $valuesSuperLink */
        $valuesSuperLink = []; // catalog_product_super_link
        /** @var Zend_Db_Statement_Pdo $query */
        $query = $connection->query($configurableSelect);
        /** @var mixed[] $stores */
        $stores = $storeHelper->getStores('store_id');

        /** @var string[] $row */
        while ($row = $query->fetch()) {
            if (!isset($row['_axes'])) {
                continue;
            }

            /** @var string[] $attributes */
            $attributes = explode(',', $row['_axes']);
            /** @var int $position */
            $position = 0;

            /** @var string $id */
            foreach ($attributes as $id) {
                if (!is_numeric($id) || !isset($row['_entity_id']) || !isset($row['_children'])) {
                    continue;
                }

                /** @var bool $hasOptions */
                $hasOptions = (bool)$connection->fetchOne(
                    $connection->select()->from(
                        $resourceEntities->getTable('eav/attribute_option'),
                        [new Zend_Db_Expr(1)]
                    )->where('attribute_id = ?', $id)->limit(1)
                );

                if (!$hasOptions) {
                    continue;
                }

                /** @var mixed[] $values */
                $values = [
                    'product_id'   => $row['_entity_id'],
                    'attribute_id' => $id,
                    'position'     => $position++,
                ];
                $connection->insertOnDuplicate(
                    $resourceEntities->getTable('catalog/product_super_attribute'),
                    $values,
                    []
                );

                /** @var string $superAttributeId */
                $superAttributeId = $connection->fetchOne(
                    $connection->select()->from($resourceEntities->getTable('catalog/product_super_attribute'))->where(
                        'attribute_id = ?',
                        $id
                    )->where('product_id = ?', $row['_entity_id'])->limit(1)
                );

                /**
                 * @var int     $storeId
                 * @var mixed[] $affected
                 */
                foreach ($stores as $storeId => $affected) {
                    $valuesLabels[] = [
                        'product_super_attribute_id' => $superAttributeId,
                        'store_id'                   => $storeId,
                        'use_default'                => 0,
                        'value'                      => '',
                    ];
                }

                /** @var string[] $children */
                $children = explode(',', $row['_children']);
                /** @var string $child */
                foreach ($children as $child) {
                    /** @var int $childId */
                    $childId = (int)$connection->fetchOne(
                        $connection->select()->from($resourceEntities->getTable('catalog/product'), ['entity_id'])->where('sku = ?', $child)->limit(1)
                    );

                    if (!$childId) {
                        continue;
                    }

                    $valuesRelations[] = [
                        'parent_id' => $row['_entity_id'],
                        'child_id'  => $childId,
                    ];

                    $valuesSuperLink[] = [
                        'product_id' => $childId,
                        'parent_id'  => $row['_entity_id'],
                    ];
                }

                if (count($valuesSuperLink) > $this->maxConfigurableInsertion) {
                    $connection->insertOnDuplicate(
                        $resourceEntities->getTable('catalog/product_super_attribute_label'),
                        $valuesLabels,
                        []
                    );

                    $connection->insertOnDuplicate(
                        $resourceEntities->getTable('catalog/product_relation'),
                        $valuesRelations,
                        []
                    );

                    $connection->insertOnDuplicate(
                        $resourceEntities->getTable('catalog/product_super_link'),
                        $valuesSuperLink,
                        []
                    );

                    $valuesLabels    = [];
                    $valuesRelations = [];
                    $valuesSuperLink = [];
                }
            }
        }

        if (count($valuesSuperLink) > 0) {
            $connection->insertOnDuplicate(
                $resourceEntities->getTable('catalog/product_super_attribute_label'),
                $valuesLabels,
                []
            );

            $connection->insertOnDuplicate(
                $resourceEntities->getTable('catalog/product_relation'),
                $valuesRelations,
                []
            );

            $connection->insertOnDuplicate(
                $resourceEntities->getTable('catalog/product_super_link'),
                $valuesSuperLink,
                []
            );
        }
    }

    /**
     * Set website
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    public function setWebsites($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var string $tmpTable */
        $tmpTable = $resourceEntities->getTableName();
        /** @var Pimgento_Api_Helper_Store $storeHelper */
        $storeHelper = Mage::helper('pimgento_api/store');
        /** @var mixed[] $websites */
        $websites = $storeHelper->getStores('website_id');

        /**
         * @var int     $websiteId
         * @var mixed[] $affected
         */
        foreach ($websites as $websiteId => $affected) {
            if ($websiteId === 0) {
                continue;
            }

            /** @var Varien_Db_Select $select */
            $select = $connection->select()->from(
                $tmpTable,
                [
                    'product_id' => '_entity_id',
                    'website_id' => new Zend_Db_Expr($websiteId),
                ]
            );

            $connection->query(
                $connection->insertFromSelect(
                    $select,
                    $resource->getTableName('catalog_product_website'),
                    ['product_id', 'website_id'],
                    Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE
                )
            );
        }
    }

    /**
     * Set categories
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Mage_Core_Exception|Pimgento_Api_Exception|Task_Executor_Exception
     */
    public function setCategories($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var string $tmpTable */
        $tmpTable = $resourceEntities->getTableName();

        if (!$connection->tableColumnExists($tmpTable, 'categories')) {
            $task->stop($this->getHelper()->__('Column categories not found'));
        }

        /** @var Varien_Db_Select $select */
        $select = $connection->select()->from(['c' => $resourceEntities->getTable('pimgento_api/entities')], [])->joinInner(
            ['p' => $tmpTable],
            'FIND_IN_SET(`c`.`code`, `p`.`categories`) AND `c`.`import` = "category"',
            [
                'category_id' => 'c.entity_id',
                'product_id'  => 'p._entity_id',
            ]
        )->joinInner(
            ['e' => $resourceEntities->getTable('catalog/category')],
            'c.entity_id = e.entity_id',
            []
        );

        $connection->query(
            $connection->insertFromSelect(
                $select,
                $resourceEntities->getTable('catalog/category_product'),
                ['category_id', 'product_id'],
                1
            )
        );

        /** @var Varien_Db_Select $selectToDelete */
        $selectToDelete = $connection->select()->from(['c' => $resourceEntities->getTable('pimgento_api/entities')], [])->joinInner(
            ['p' => $tmpTable],
            '!FIND_IN_SET(`c`.`code`, `p`.`categories`) AND `c`.`import` = "category"',
            [
                'category_id' => 'c.entity_id',
                'product_id'  => 'p._entity_id',
            ]
        )->joinInner(
            ['e' => $resourceEntities->getTable('catalog/category')],
            'c.entity_id = e.entity_id',
            []
        );

        $connection->delete(
            $resourceEntities->getTable('catalog/category_product'),
            sprintf('(category_id, product_id) IN (%s)', $selectToDelete->assemble())
        );
    }

    /**
     * Init stock
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    public function initStock($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var string $tmpTable */
        $tmpTable = $resourceEntities->getTableName();
        /** @var mixed[] $values */
        $values = [
            'product_id'                => '_entity_id',
            'stock_id'                  => new Zend_Db_Expr(1),
            'qty'                       => new Zend_Db_Expr(0),
            'is_in_stock'               => new Zend_Db_Expr(0),
            'low_stock_date'            => new Zend_Db_Expr('NULL'),
            'stock_status_changed_auto' => new Zend_Db_Expr(0),
        ];

        /** @var Varien_Db_Select $select */
        $select = $connection->select()->from($tmpTable, $values);

        $connection->query(
            $connection->insertFromSelect(
                $select,
                $resourceEntities->getTable('cataloginventory/stock_item'),
                array_keys($values),
                Varien_Db_Adapter_Interface::INSERT_IGNORE
            )
        );
    }

    /**
     * Update related, up-sell and cross-sell products
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    public function setRelated($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var string $tmpTable */
        $tmpTable = $resourceEntities->getTableName();
        /** @var string $entitiesTable */
        $entitiesTable = $resourceEntities->getTable('pimgento_api/entities');
        /** @var string $productsTable */
        $productsTable = $resourceEntities->getTable('catalog/product');
        /** @var string $linkTable */
        $linkTable = $resourceEntities->getTable('catalog/product_link');
        /** @var string $linkAttributeTable */
        $linkAttributeTable = $resourceEntities->getTable('catalog/product_link_attribute');
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var mixed[] $related */
        $related = [];

        /** @var int $linkType */
        /** @var string[] $associationNames */
        foreach ($this->associationTypes as $linkType => $associationNames) {
            if (empty($associationNames)) {
                continue;
            }
            /** @var string $associationName */
            foreach ($associationNames as $associationName) {
                if (!empty($associationName) && $connection->tableColumnExists($tmpTable, $associationName)) {
                    $related[$linkType][] = sprintf('`p`.`%s`', $associationName);
                }
            }
        }

        /**
         * @var int      $typeId
         * @var string[] $columns
         */
        foreach ($related as $typeId => $columns) {
            /** @var string $concat */
            $concat = sprintf('CONCAT_WS(",", %s)', implode(', ', $columns));
            /** @var Varien_Db_Select $select */
            $select = $connection->select()->from(['c' => $entitiesTable], [])->joinInner(
                ['p' => $tmpTable],
                sprintf('FIND_IN_SET(`c`.`code`, %s) AND `c`.`import` = "%s"', $concat, $this->getCode()),
                [
                    'product_id'        => 'p._entity_id',
                    'linked_product_id' => 'c.entity_id',
                    'link_type_id'      => new Zend_Db_Expr($typeId),
                ]
            )->joinInner(['e' => $productsTable], 'c.entity_id = e.entity_id', []);

            /* Remove old link */
            $connection->delete(
                $linkTable,
                ['(product_id, linked_product_id, link_type_id) NOT IN (?)' => $select, 'link_type_id = ?' => $typeId]
            );

            /* Insert new link */
            $connection->query(
                $connection->insertFromSelect(
                    $select,
                    $linkTable,
                    ['product_id', 'linked_product_id', 'link_type_id'],
                    Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE
                )
            );

            /* Insert position */
            $attributeId = $connection->fetchOne(
                $connection->select()->from($linkAttributeTable, ['product_link_attribute_id'])->where(
                    'product_link_attribute_code = ?',
                    'position'
                )->where('link_type_id = ?', $typeId)
            );

            if ($attributeId) {
                /** @var Varien_Db_Select $select */
                $select = $connection->select()->from(
                    $linkTable,
                    [new Zend_Db_Expr($attributeId), 'link_id', 'link_id']
                )->where('link_type_id = ?', $typeId);

                $connection->query(
                    $connection->insertFromSelect(
                        $select,
                        $resource->getTableName('catalog_product_link_attribute_int'),
                        ['product_link_attribute_id', 'link_id', 'value'],
                        Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE
                    )
                );
            }
        }
    }

    /**
     * Import the medias
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Mage_Core_Exception|Pimgento_Api_Exception|Task_Executor_Exception|Zend_Db_Statement_Exception
     */
    public function importMedia($task)
    {
        if (!$this->getConfigurationHelper()->isMediaImportEnabled()) {
            $task->setStepWarning($this->getHelper()->__('Media import is not enabled'));
            $task->setStepMessage($this->getHelper()->__('Step skipped.'));

            return;
        }

        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var string $tmpTable */
        $tmpTable = $resourceEntities->getTableName();
        /** @var string[] $gallery */
        $gallery = $this->getConfigurationHelper()->getMediaImportGalleryColumns();

        if (empty($gallery)) {
            $task->stop($this->getHelper()->__('PIM Images Attributes is empty'));
        }

        /** @var string[] $data */
        $data = [
            'entity_id' => '_entity_id',
            'sku'       => 'code',
        ];
        /** @var string $image */
        foreach ($gallery as $image) {
            if (!$connection->tableColumnExists($tmpTable, $image)) {
                $task->setStepWarning($this->getHelper()->__('%1$s attribute does not exist', $image));

                continue;
            }
            $data[$image] = $image;
        }

        /** @var Varien_Db_Select $select */
        $select = $connection->select()->from($tmpTable, $data);

        /** @var Zend_Db_Statement_Pdo $query */
        $query = $connection->query($select);

        /** @var Pimgento_Api_Helper_Image $imageHelper */
        $imageHelper = Mage::helper('pimgento_api/image');
        /** @var Mage_Eav_Model_Entity_Attribute $entityAttribute */
        $entityAttribute = Mage::getModel('eav/entity_attribute');
        /** @var Mage_Eav_Model_Attribute $galleryAttribute */
        $galleryAttribute = $entityAttribute->loadByCode(Mage_Catalog_Model_Product::ENTITY, 'media_gallery');
        /** @var string $mediaGalleryValueTable */
        $mediaGalleryValueTable = $resourceEntities->getValueTable('catalog/product', 'media_gallery_value');
        /** @var string $mediaGalleryValueTable */
        $mediaGalleryTable = $resourceEntities->getValueTable('catalog/product', 'media_gallery');
        /** @var string $productImageTable */
        $productImageTable = $resourceEntities->getValueTable('catalog/product', 'varchar');

        /** @var string[] $row */
        while (($row = $query->fetch())) {
            /** @var string[] $files */
            $files = [];
            /**
             * @var int    $key
             * @var string $image
             */
            foreach ($gallery as $key => $image) {
                if (!isset($row[$image])) {
                    continue;
                }

                if (!$row[$image]) {
                    continue;
                }

                /** @var mixed[] $media */
                $media = $this->getClient()->getProductMediaFileApi()->get($row[$image]);
                /** @var string $name */
                $name = basename($media['code']);

                if (!$imageHelper->mediaFileExists($name)) {
                    $binary = $this->getClient()->getProductMediaFileApi()->download($row[$image]);
                    $imageHelper->saveMediaFile($name, $binary);
                }

                /** @var string $file */
                $file = $imageHelper->getMediaFilePath($name);

                /** @var int $valueId */
                $valueId = $connection->fetchOne(
                    $connection->select()->from($mediaGalleryTable, ['value_id'])->where('value = ?', $file)
                );

                if (!$valueId) {
                    /** @var int $valueId */
                    $valueId = $connection->fetchOne(
                        $connection->select()->from($mediaGalleryTable, [new Zend_Db_Expr('MAX(`value_id`)')])
                    );
                    $valueId += 1;
                }

                /** @var mixed[] $data */
                $data = [
                    'value_id'     => $valueId,
                    'attribute_id' => $galleryAttribute->getId(),
                    'entity_id'    => $row['entity_id'],
                    'value'        => $file,
                ];
                $connection->insertOnDuplicate($mediaGalleryTable, $data, array_keys($data));

                /** @var mixed[] $data */
                $data = [
                    'value_id' => $valueId,
                    'store_id' => 0,
                    'label'    => '',
                    'position' => $key,
                    'disabled' => 0,
                ];
                $connection->insertOnDuplicate($mediaGalleryValueTable, $data, array_keys($data));

                /** @var string[] $columns */
                $columns = $this->getConfigurationHelper()->getMediaImportImagesColumns();

                /** @var string[] $column */
                foreach ($columns as $column) {
                    if ($column['column'] !== $image) {
                        continue;
                    }
                    /** @var mixed[] $data */
                    $data = [
                        'attribute_id'   => $column['attribute'],
                        'store_id'       => 0,
                        'entity_id'      => $row['entity_id'],
                        'entity_type_id' => new Zend_Db_Expr($this->getProductEntityTypeId()),
                        'value'          => $file,
                    ];
                    $connection->insertOnDuplicate($productImageTable, $data, array_keys($data));
                }

                $files[] = $file;
            }

            $connection->delete(
                $mediaGalleryTable,
                [
                    'value NOT IN (?)' => $files,
                    'entity_id = ?'    => $row['entity_id'],
                ]
            );
        }
    }

    /**
     * Import the assets
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Mage_Core_Exception|Pimgento_Api_Exception|Task_Executor_Exception|Zend_Db_Statement_Exception
     */
    public function importAsset($task)
    {
        if ($this->getConfigurationHelper()->isCommunityVersion()) {
            $task->setStepWarning($this->getHelper()->__('Only available on Pim Enterprise'));
            $task->setStepMessage($this->getHelper()->__('Step skipped.'));

            return;
        }

        if (!$this->getConfigurationHelper()->isAssetImportEnabled()) {
            $task->setStepWarning($this->getHelper()->__('Asset import is not enabled'));
            $task->setStepMessage($this->getHelper()->__('Step skipped.'));

            return;
        }

        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resourceEntities->getReadConnection();
        /** @var string $tmpTable */
        $tmpTable = $resourceEntities->getTableName();
        /** @var string[] $gallery */
        $gallery = $this->getConfigurationHelper()->getAssetImportGalleryColumns();

        if (empty($gallery)) {
            $task->stop($this->getHelper()->__('PIM Asset Attributes is empty'));
        }

        /** @var string[] $data */
        $data = [
            'entity_id' => '_entity_id',
            'sku'       => 'code',
        ];
        /** @var string $asset */
        foreach ($gallery as $asset) {
            if (!$connection->tableColumnExists($tmpTable, $asset)) {
                $task->setStepWarning($this->getHelper()->__('%1$s attribute does not exist', $asset));

                continue;
            }
            $data[$asset] = $asset;
        }

        /** @var Varien_Db_Select $select */
        $select = $connection->select()->from($tmpTable, $data);

        /** @var Zend_Db_Statement_Pdo $query */
        $query = $connection->query($select);

        /** @var Pimgento_Api_Helper_Image $imageHelper */
        $imageHelper = Mage::helper('pimgento_api/image');
        /** @var Mage_Eav_Model_Entity_Attribute $entityAttribute */
        $entityAttribute = Mage::getModel('eav/entity_attribute');
        /** @var Mage_Eav_Model_Attribute $galleryAttribute */
        $galleryAttribute = $entityAttribute->loadByCode(Mage_Catalog_Model_Product::ENTITY, 'media_gallery');
        /** @var string $mediaGalleryValueTable */
        $mediaGalleryValueTable = $resourceEntities->getValueTable('catalog/product', 'media_gallery_value');
        /** @var string $mediaGalleryValueTable */
        $mediaGalleryTable = $resourceEntities->getValueTable('catalog/product', 'media_gallery');
        /** @var string $productImageTable */
        $productImageTable = $resourceEntities->getValueTable('catalog/product', 'varchar');

        /** @var string[] $row */
        while (($row = $query->fetch())) {
            /** @var string[] $files */
            $files = [];
            /** @var string $asset */
            foreach ($gallery as $asset) {
                if (!isset($row[$asset])) {
                    continue;
                }

                if (!$row[$asset]) {
                    continue;
                }

                /** @var string[] $assets */
                $assets = explode(',', $row[$asset]);
                /**
                 * @var int    $key
                 * @var string $code
                 */
                foreach ($assets as $key => $code) {
                    /** @var mixed[] $media */
                    $media = $this->getClient()->getAssetApi()->get($code);
                    if (!isset($media['code'], $media['reference_files'])) {
                        continue;
                    }

                    /** @var string[] $reference */
                    $reference = reset($media['reference_files']);
                    if (!$reference) {
                        continue;
                    }

                    /** @var string $name */
                    $name = basename($reference['code']);

                    if (!$imageHelper->mediaFileExists($name)) {
                        if ($reference['locale']) {
                            /** @var Psr\Http\Message\StreamInterface $binary */
                            $binary = $this->getClient()->getAssetReferenceFileApi()->downloadFromLocalizableAsset(
                                $media['code'],
                                $reference['locale']
                            );
                        } else {
                            /** @var Psr\Http\Message\StreamInterface $binary */
                            $binary = $this->getClient()->getAssetReferenceFileApi()->downloadFromNotLocalizableAsset(
                                $media['code']
                            );
                        }
                        $imageHelper->saveMediaFile($name, $binary);
                    }

                    /** @var string $file */
                    $file = $imageHelper->getMediaFilePath($name);

                    /** @var int $valueId */
                    $valueId = $connection->fetchOne(
                        $connection->select()->from($mediaGalleryTable, ['value_id'])->where('value = ?', $file)
                    );

                    if (!$valueId) {
                        /** @var int $valueId */
                        $valueId = $connection->fetchOne(
                            $connection->select()->from($mediaGalleryTable, [new Zend_Db_Expr('MAX(`value_id`)')])
                        );
                        $valueId += 1;
                    }

                    /** @var mixed[] $data */
                    $data = [
                        'value_id'     => $valueId,
                        'attribute_id' => $galleryAttribute->getId(),
                        'entity_id'    => $row['entity_id'],
                        'value'        => $file,
                    ];
                    $connection->insertOnDuplicate($mediaGalleryTable, $data, array_keys($data));

                    /** @var mixed[] $data */
                    $data = [
                        'value_id' => $valueId,
                        'store_id' => 0,
                        'label'    => $media['description'],
                        'position' => $key,
                        'disabled' => 0,
                    ];
                    $connection->insertOnDuplicate($mediaGalleryValueTable, $data, array_keys($data));

                    if (empty($files)) {
                        /** @var mixed[] $entities */
                        $attributes = [
                            Mage::getModel('eav/entity_attribute')->loadByCode(
                                Mage_Catalog_Model_Product::ENTITY,
                                'image'
                            ),
                            Mage::getModel('eav/entity_attribute')->loadByCode(
                                Mage_Catalog_Model_Product::ENTITY,
                                'small_image'
                            ),
                            Mage::getModel('eav/entity_attribute')->loadByCode(
                                Mage_Catalog_Model_Product::ENTITY,
                                'thumbnail'
                            ),
                        ];

                        /** @var Mage_Eav_Model_Entity_Attribute[] $attribute */
                        foreach ($attributes as $attribute) {
                            if (!$attribute) {
                                continue;
                            }
                            /** @var mixed[] $data */
                            $data = [
                                'attribute_id' => $attribute->getId(),
                                'store_id'     => 0,
                                'entity_id'    => $row['entity_id'],
                                'value'        => $file,
                            ];
                            $connection->insertOnDuplicate($productImageTable, $data, array_keys($data));
                        }
                    }

                    $files[] = $file;
                }
            }

            $connection->delete(
                $mediaGalleryTable,
                [
                    'value NOT IN (?)' => $files,
                    'entity_id = ?'    => $row['entity_id'],
                ]
            );
        }
    }

    /**
     * Drop table
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

    /**
     * Get maximum configurable per insert value
     *
     * @return int
     */
    public function getMaxConfigurableInsertion()
    {
        return $this->maxConfigurableInsertion;
    }

    /**
     * Get allowed product type_id
     *
     * @return string[]
     */
    public function getAllowedTypeId()
    {
        return $this->allowedTypeId;
    }

    /**
     * Retrieve product entity type id
     *
     * @return int
     */
    public function getProductEntityTypeId()
    {
        /** @var Pimgento_Api_Helper_Entities $entitiesHelper */
        $entitiesHelper = Mage::helper('pimgento_api/entities');

        return $entitiesHelper->getProductEntityTypeId();
    }

    /**
     * Retrieve product default attribute set id
     *
     * @return int
     */
    public function getProductDefaultAttributeSetId()
    {
        /** @var Pimgento_Api_Helper_Entities $entitiesHelper */
        $entitiesHelper = Mage::helper('pimgento_api/entities');

        return $entitiesHelper->getProductDefaultAttributeSetId();
    }

    /**
     * Retrieve excluded columns
     *
     * @return string[]
     */
    public function getExcludedColumns()
    {
        return $this->excludedColumns;
    }
}
