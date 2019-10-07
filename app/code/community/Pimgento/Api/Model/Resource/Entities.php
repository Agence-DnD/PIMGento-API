<?php

/**
 * Class Pimgento_Api_Model_Resource_Entities
 *
 * @category  Class
 * @package   Pimgento_Api_Model_Resource_Entities
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Resource_Entities extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Entities table prefix
     *
     * @var string TMP_TABLE_KEYWORD
     */
    const TMP_TABLE_KEYWORD = 'tmp';
    /**
     * Entities excluded columns
     *
     * @var string[] EXCLUDED_COLUMNS
     */
    const EXCLUDED_COLUMNS = ['_links'];
    /**
     * Akeneo entity universal code key
     *
     * @var string PIM_CODE_KEY
     */
    const PIM_CODE_KEY = 'code';
    /**
     * Pimgento entity code product
     *
     * @var string ENTITY_CODE_PRODUCT
     */
    const ENTITY_CODE_PRODUCT = 'product';
    /**
     * Entity table code
     *
     * @var mixed $entityCode
     */
    protected $entityCode;
    /**
     * Entity temporary table name
     *
     * @var string $tableName
     */
    protected $tableName;
    /**
     * Columns from Api response
     *
     * @var string[] $columnNames
     */
    protected $columnNames = [];
    /**
     * Product attributes to pass if empty value
     *
     * @var string[] $passIfEmpty
     */
    protected $passIfEmpty = [
        'price',
    ];
    /**
     * Mapped catalog attributes with relative scope
     *
     * @var string[] $attributeScopeMapping
     */
    protected $attributeScopeMapping = [];

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('pimgento_api/entities', null);
    }

    /**
     * Return current import entity code
     *
     * @return string
     */
    public function getEntityCode()
    {
        return $this->entityCode;
    }

    /**
     * Set current import entity code
     *
     * @param string $entityCode
     *
     * @return Pimgento_Api_Model_Resource_Entities
     */
    public function setEntityCode($entityCode)
    {
        $this->entityCode = $entityCode;

        return $this;
    }

    /**
     * Retrieve attributes to pass if empty value
     *
     * @return string[]
     */
    public function getPassIfEmpty()
    {
        return $this->passIfEmpty;
    }

    /**
     * Create pimgento_api/entities table
     *
     * @return Pimgento_Api_Model_Resource_Entities
     * @throws Zend_Db_Exception
     */
    public function createMainTable()
    {
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->_getWriteAdapter();
        /** @var string $entitiesTableName */
        $entitiesTableName = $this->getMainTable();
        /** @var Mage_Core_Model_Resource $coreResource */
        $coreResource = Mage::getSingleton('core/resource');
        /** @var string[] $idxFields */
        $idxFields = ['import', 'code', 'entity_id'];
        /** @var string $idxName */
        $idxName = $coreResource->getIdxName(
            'pimgento_entities',
            $idxFields,
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        );
        /** @var Varien_Db_Ddl_Table $entitiesTable */
        $entitiesTable = $adapter->newTable($entitiesTableName);

        $entitiesTable->setComment('Pimgento Entities Relation');
        $entitiesTable->addColumn(
            $this->getIdFieldName(),
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'ID'
        );
        $entitiesTable->addColumn(
            'import',
            Varien_Db_Ddl_Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Import type'
        );
        $entitiesTable->addColumn(
            'code',
            Varien_Db_Ddl_Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Pim Code'
        );
        $entitiesTable->addColumn(
            'entity_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            11,
            ['nullable' => true],
            'Magento Entity Id'
        );
        $entitiesTable->addColumn(
            'created_at',
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT],
            'Creation Time'
        );
        $entitiesTable->addIndex(
            $idxName,
            $idxFields,
            ['type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE]
        );

        $adapter->createTable($entitiesTable);

        return $this;
    }

    /**
     * Drop pimgento_api/entities table
     *
     * @return Pimgento_Api_Model_Resource_Entities
     */
    public function dropMainTable()
    {
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->_getWriteAdapter();
        /** @var string $entitiesTableName */
        $entitiesTableName = $this->getMainTable();

        $adapter->dropTable($entitiesTableName);

        return $this;
    }

    /**
     * Create temporary table from api result
     *
     * @param mixed[] $result
     *
     * @return bool
     * @throws Pimgento_Api_Exception|Zend_Db_Exception
     */
    public function createTemporaryTableFromApi(array $result)
    {
        if (empty($result)) {
            throw new Pimgento_Api_Exception('Empty results from Api');
        }

        /** @var string[] $columnNames */
        $columnNames = $this->getColumnNamesFromResult($result);
        if (empty($columnNames)) {
            throw new Pimgento_Api_Exception('Empty column names from Api');
        }

        $this->createTemporaryTable($columnNames);

        return true;
    }

    /**
     * Retrieve column names only from API result
     *
     * @param mixed[] $result
     *
     * @return string[]
     */
    protected function getColumnNamesFromResult(array $result)
    {
        if (count($this->columnNames) > 0) {
            return $this->columnNames;
        }
        /** @var string[] $columns */
        $columns           = $this->getColumnsFromResult($result);
        $this->columnNames = array_keys($columns);

        return $this->columnNames;
    }

    /**
     * Retrieve table column names from Api result
     *
     * @param mixed[] $result
     *
     * @return string[]
     */
    protected function getColumnsFromResult(array $result)
    {
        /** @var string[] $columns */
        $columns = [];
        /**
         * @var string $key
         * @var mixed  $value
         */
        foreach ($result as $key => $value) {
            if (in_array($key, static::EXCLUDED_COLUMNS)) {
                continue;
            }

            if (!is_array($value)) {
                $columns[$key] = $value;

                continue;
            }

            if (empty($value)) {
                $columns[$key] = null;

                continue;
            }

            /**
             * @var mixed $locale
             * @var mixed $localeValue
             */
            foreach ($value as $locale => $localeValue) {
                if (is_numeric($locale)) {
                    $columns[$key] = implode(',', $value);

                    break;
                }

                /** @var mixed $data */
                $data = $localeValue;
                if (is_array($localeValue)) {
                    $data = implode(',', $localeValue);
                }
                /** @var string $columnKey */
                $columnKey           = sprintf('%s-%s', $key, $locale);
                $columns[$columnKey] = $data;
            }
        }

        return $columns;
    }

    /**
     * Drop temporary table if exists then create it
     *
     * @param string[] $fields
     *
     * @return Pimgento_Api_Model_Resource_Entities
     * @throws Pimgento_Api_Exception|Zend_Db_Exception
     */
    public function createTemporaryTable(array $fields)
    {
        /** @var Pimgento_Api_Helper_Entities $helper */
        $helper = Mage::helper('pimgento_api/entities');
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->_getWriteAdapter();
        /* Delete table if exists */
        $this->dropTemporaryTable();
        /** @var string $tableName */
        $tableName = $this->getTableName();

        /* Create new table */
        /** @var Varien_Db_Ddl_Table $table */
        $table = $adapter->newTable($tableName);

        $fields = array_diff($fields, ['identifier']);

        $table->addColumn(
            'identifier',
            Varien_Db_Ddl_Table::TYPE_VARBINARY,
            255,
            [],
            'identifier'
        );

        $table->addIndex(
            'UNIQUE_IDENTIFIER',
            'identifier',
            ['type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE]
        );

        /** @var string $field */
        foreach ($fields as $field) {
            if (empty($field)) {
                continue;
            }
            /** @var string $column */
            $column = $helper->formatColumn($field);
            $table->addColumn(
                $column,
                Varien_Db_Ddl_Table::TYPE_TEXT,
                null,
                [],
                $column
            );
        }

        $table->addColumn(
            '_entity_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            11,
            [],
            'Entity Id'
        );

        $table->addIndex(
            'UNIQUE_ENTITY_ID',
            '_entity_id',
            ['type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE]
        );

        $table->addColumn(
            '_is_new',
            Varien_Db_Ddl_Table::TYPE_SMALLINT,
            1,
            ['default' => 0],
            'Is New'
        );

        $table->setOption('type', 'MYISAM');

        $adapter->createTable($table);

        return $this;
    }

    /**
     * Drop Temporary Table
     *
     * @return Pimgento_Api_Model_Resource_Entities
     * @throws Pimgento_Api_Exception
     */
    public function dropTemporaryTable()
    {
        /** @var string $tableName */
        $tableName = $this->getTableName();
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->_getWriteAdapter();
        $adapter->resetDdlCache($tableName);
        $adapter->dropTable($tableName);

        return $this;
    }

    /**
     * Get temporary table name
     *
     * @return string
     * @throws Pimgento_Api_Exception
     */
    public function getTableName()
    {
        if (!empty($this->tableName)) {
            return $this->tableName;
        }

        if (empty($this->getEntityCode())) {
            throw new Pimgento_Api_Exception(Mage::helper('pimgento_api')->__('Entity code not set in %s', __CLASS__));
        }

        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->getReadConnection();
        /** @var string[] $fragments */
        $fragments = [
            $this->getMainTable(),
            self::TMP_TABLE_KEYWORD,
            $this->getEntityCode(),
        ];
        /** @var string $gluedFragments */
        $gluedFragments  = implode('_', $fragments);
        $this->tableName = $adapter->getTableName($gluedFragments);

        return $this->tableName;
    }

    /**
     * Insert data in the temporary table
     *
     * @param string[] $columns
     *
     * @return bool
     * @throws Pimgento_Api_Exception
     */
    public function insertDataFromApi(array $columns)
    {
        if (empty($columns)) {
            return false;
        }

        /** @var string[] $fields */
        $fields = array_diff_key($columns, ['identifier' => null]);
        $fields = array_keys($fields);

        /** @var string $tableName */
        $tableName = $this->getTableName();
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->_getWriteAdapter();
        /** @var string $columnName */
        foreach ($columns as $columnName => $columnValue) {
            if ($adapter->tableColumnExists($tableName, $columnName)) {
                continue;
            }
            $adapter->addColumn($tableName, $columnName, 'TEXT NULL');
        }

        $adapter->insertOnDuplicate($tableName, $columns, $fields);

        return true;
    }

    /**
     * Match entity with code
     *
     * @param string $entityCode
     * @param string $entityTableAlias
     * @param string $primaryKey
     * @param string $prefix
     *
     * @return Pimgento_Api_Model_Resource_Entities
     * @throws Pimgento_Api_Exception|Zend_Db_Statement_Exception|Exception
     */
    public function matchEntity($entityCode, $entityTableAlias, $primaryKey, $prefix = '')
    {
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->_getWriteAdapter();
        /** @var string $tableName */
        $tableName = $this->getTableName();
        /** @var string $entityTable */
        $entityTable = $this->getTable($entityTableAlias);
        /** @var string $pimgentoTable */
        $pimgentoTable = $this->getMainTable();
        /** @var string $entitiesIdFieldName */
        $entitiesIdFieldName = 'entity_id';
        /** @var string $import */
        $import = $this->getEntityCode();

        $adapter->delete($tableName, [sprintf('%s = ?', $entityCode) => '']);

        /** @var string $entityCodeColumnName */
        $entityCodeColumnName = sprintf('t.`%s`', $entityCode);
        if ($prefix !== '') {
            /* Use new error-free separator */
            $entityCodeColumnName = sprintf('CONCAT(t.`%s`, "-", t.`%s`)', $prefix, $entityCode);

            /* Legacy: update columns still using former "_" separator */
            /** @var string $oldEntityCodeColumnName */
            $oldEntityCodeColumnName = sprintf('CONCAT(t.`%s`, "_", t.`%s`)', $prefix, $entityCode);
            /** @var string $update */
            $update = 'UPDATE `' . $pimgentoTable . '` AS `e`, `' . $tableName . '` AS `t` SET e.code = ' . $entityCodeColumnName . ' WHERE e.code = ' . $oldEntityCodeColumnName;

            $adapter->query($update);
        }

        /* Update entity_id column from pimgento_entities table */
        $adapter->query(
            'UPDATE `' . $tableName . '` t
            SET `_entity_id` = (
                SELECT `' . $entitiesIdFieldName . '` FROM `' . $pimgentoTable . '` c
                WHERE ' . $entityCodeColumnName . ' = c.`code`
                    AND c.`import` = "' . $import . '"
                    LIMIT 1
            )'
        );

        /* Set entity_id for new entities */
        /** @var string $autoIncrement */
        $autoIncrement = $this->getIncrementId($entityTable);

        $adapter->query(sprintf('SET @id = %s', $autoIncrement));
        /** @var Zend_Db_Expr[] $values */
        $values = [
            '_entity_id' => new Zend_Db_Expr('@id := @id + 1'),
            '_is_new'    => new Zend_Db_Expr('1'),
        ];
        $adapter->update($tableName, $values, '_entity_id IS NULL');

        /* Update pimgento_entities table with code and new entity_id */
        /** @var Varien_Db_Select $select */
        $select = $adapter->select()->from(
            ['t' => $tableName],
            [
                'import'             => new Zend_Db_Expr(sprintf("'%s'", $import)),
                'code'               => new Zend_Db_Expr($entityCodeColumnName),
                $entitiesIdFieldName => '_entity_id',
            ]
        )->where('_is_new = ?', 1);

        $adapter->query(
            $adapter->insertFromSelect(
                $select,
                $pimgentoTable,
                ['import', 'code', $entitiesIdFieldName],
                Varien_Db_Adapter_Interface::INSERT_IGNORE
            )
        );

        /* Update entity table auto increment */
        /** @var int $count */
        $count = (int)$adapter->fetchOne(
            $adapter->select()->from($tableName, [new Zend_Db_Expr('COUNT(*)')])->where('_is_new = ?', 1)
        );
        if ($count === 0) {
            return $this;
        }

        /** @var string $maxCode */
        $maxCode = $adapter->fetchOne(
            $adapter->select()->from($pimgentoTable, new Zend_Db_Expr(sprintf('MAX(`%s`)', $entitiesIdFieldName)))->where(
                'import = ?',
                $import
            )
        );
        /** @var string $maxEntity */
        $maxEntity = $adapter->fetchOne(
            $adapter->select()->from($entityTable, new Zend_Db_Expr(sprintf('MAX(`%s`)', $primaryKey)))
        );

        $adapter->changeTableAutoIncrement($entityTable, max((int)$maxCode, (int)$maxEntity) + 1);

        return $this;
    }

    /**
     * Retrieve next entity id from entity table
     *
     * @param string $entityTableName
     *
     * @return int
     * @throws Zend_Db_Statement_Exception
     */
    protected function getIncrementId($entityTableName)
    {
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->getReadConnection();
        /** @var string $showQuery */
        $showQuery = sprintf('SHOW TABLE STATUS LIKE "%s"', $entityTableName);

        /** @var Zend_Db_Statement_Interface $result */
        $result = $adapter->query($showQuery);
        /** @var mixed $row */
        $row = $result->fetch();

        return (int)$row['Auto_increment'] + 1;
    }

    /**
     * Set values to attributes
     *
     * @param string  $entityCode
     * @param string  $entityTable
     * @param mixed[] $values
     * @param int     $entityTypeId
     * @param int     $storeId
     * @param int     $mode
     *
     * @return Pimgento_Api_Model_Resource_Entities
     * @throws Pimgento_Api_Exception
     */
    public function setValues(
        $entityCode,
        $entityTable,
        $values,
        $entityTypeId,
        $storeId,
        $mode = Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE
    ) {
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->_getWriteAdapter();
        /** @var string $tableName */
        $tableName = $this->getTableName();

        /**
         * @var string $code
         * @var mixed  $value
         */
        foreach ($values as $code => $value) {
            /** @var string[] $attribute */
            $attribute = $this->getAttribute($code, $entityTypeId);

            if (empty($attribute)) {
                continue;
            }

            if (empty($attribute['backend_type']) || $attribute['backend_type'] === 'static') {
                continue;
            }

            /** @var Varien_Db_Select $select */
            $select = $adapter->select()->from(
                $tableName,
                [
                    'entity_type_id' => new Zend_Db_Expr($entityTypeId),
                    'attribute_id'   => new Zend_Db_Expr($attribute['attribute_id']),
                    'store_id'       => new Zend_Db_Expr($storeId),
                    'entity_id'      => '_entity_id',
                    'value'          => $value,
                ]
            );

            /** @var bool $columnExists */
            $columnExists = $this->columnExists($tableName, $value);
            if ($columnExists && ($entityCode !== self::ENTITY_CODE_PRODUCT || in_array($code, $this->getPassIfEmpty()))) {
                $select->where(sprintf('TRIM(`%s`) > ?', $value), new Zend_Db_Expr('""'));
            }

            /** @var string $backendType */
            $backendType = $attribute['backend_type'];

            if ($code === 'url_key' && Mage::getEdition() === Mage::EDITION_ENTERPRISE) {
                $backendType = 'url_key';
            }

            /** @var string $insert */
            $insert = $adapter->insertFromSelect(
                $select,
                $this->getValueTable($entityTable, $backendType),
                ['entity_type_id', 'attribute_id', 'store_id', 'entity_id', 'value'],
                $mode
            );

            $adapter->query($insert);

            if ($attribute['backend_type'] === 'datetime') {
                $values = [
                    'value' => new Zend_Db_Expr('NULL'),
                ];
                $where  = [
                    'value = ?' => '0000-00-00 00:00:00',
                ];
                $adapter->update($this->getValueTable($entityTable, $backendType), $values, $where);
            }
        }

        return $this;
    }

    /**
     * Retrieve attribute
     *
     * @param string $code
     * @param int    $entityTypeId
     *
     * @return string[]
     */
    public function getAttribute($code, $entityTypeId)
    {
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->getReadConnection();

        /** @var Varien_Db_Select $select */
        $select = $adapter->select()->from($this->getTable('eav/attribute'), ['attribute_id', 'backend_type'])->where(
            'entity_type_id = ?',
            $entityTypeId
        )->where('attribute_code = ?', $code)->limit(1);

        /** @var  $attribute */
        $attribute = $adapter->fetchRow($select);
        if (empty($attribute)) {
            return [];
        }

        return $attribute;
    }

    /**
     * Retrieve catalog attributes mapped with relative scope
     *
     * @return string[]
     */
    public function getAttributeScopeMapping()
    {
        if (!empty($this->attributeScopeMapping)) {
            return $this->attributeScopeMapping;
        }

        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var Varien_Db_Adapter_Interface $connection */
        $connection = $resource->getConnection('read');
        /** @var string $catalogAttribute */
        $catalogAttribute = $resource->getTableName('catalog/eav_attribute');
        /** @var string $eavAttribute */
        $eavAttribute = $resource->getTableName('eav/attribute');
        /** @var Varien_Db_Select $select */
        $select = $connection->select()->from(['a' => $eavAttribute], ['attribute_code'])->joinInner(['c' => $catalogAttribute], 'c.attribute_id = a.attribute_id', ['is_global']);

        /** @var string[] $attributeScopes */
        $attributeScopes = $connection->fetchPairs($select);
        if (!empty($attributeScopes)) {
            $attributeScopes             = array_merge($attributeScopes, $this->getPriceScopeMapping());
            $this->attributeScopeMapping = $attributeScopes;
        }

        return $this->attributeScopeMapping;
    }

    /**
     * Get Price scope
     * Depending on Mage_Catalog_Helper_Data::XML_PATH_PRICE_SCOPE config
     *
     * @return string[]
     */
    public function getPriceScopeMapping()
    {
        /** @var string[] $mapping */
        $mapping = [
            'price' => (string)Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
        ];
        /** @var Mage_Catalog_Helper_Data $helper */
        $helper = Mage::helper('catalog');
        /** @var bool $isGlobal */
        $isGlobal = $helper->isPriceGlobal();
        if (!$isGlobal) {
            $mapping['price'] = (string)Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE;
        }

        return $mapping;
    }

    /**
     * Check if column exists
     *
     * @param string $table
     * @param string $column
     *
     * @return bool
     */
    public function columnExists($table, $column)
    {
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->getReadConnection();

        return $adapter->tableColumnExists($table, $column);
    }

    /**
     * Copy column to an other
     *
     * @param string $tableName
     * @param string $source
     * @param string $target
     *
     * @return Pimgento_Api_Model_Resource_Entities
     */
    public function copyColumn($tableName, $source, $target)
    {
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->getReadConnection();

        if ($adapter->tableColumnExists($tableName, $source)) {
            $adapter->addColumn($tableName, $target, 'TEXT');
            $adapter->update(
                $tableName,
                [$target => new Zend_Db_Expr(sprintf('`%s`', $source))]
            );
        }

        return $this;
    }
}
