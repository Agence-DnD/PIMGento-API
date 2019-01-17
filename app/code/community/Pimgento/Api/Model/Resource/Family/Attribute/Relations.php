<?php

/**
 * Class Pimgento_Api_Model_Resource_Family_Attribute_Relations
 *
 * @category  Class
 * @package   Pimgento_Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Resource_Family_Attribute_Relations extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Magento constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('pimgento_api/family_attribute_relations', null);
    }

    /**
     * Create pimgento_api/family_attribute_relations table
     *
     * @return Pimgento_Api_Model_Resource_Family_Attribute_Relations
     * @throws Zend_Db_Exception
     */
    public function createMainTable()
    {
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->_getWriteAdapter();
        /** @var string $familyAttributeRelationTableName */
        $familyAttributeRelationTableName = $this->getMainTable();
        /** @var Varien_Db_Ddl_Table $familyAttributeRelationTable */
        $familyAttributeRelationTable = $adapter->newTable($familyAttributeRelationTableName);

        $familyAttributeRelationTable->setComment('Pimgento Family Attribute Relations');
        $familyAttributeRelationTable->addColumn(
            $this->getIdFieldName(),
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'nullable' => false, 'primary' => true],
            'ID'
        );
        $familyAttributeRelationTable->addColumn(
            'family_entity_id',
            Varien_Db_Ddl_Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Family Entity ID'
        );
        $familyAttributeRelationTable->addColumn(
            'attribute_code',
            Varien_Db_Ddl_Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Attribute Code'
        );
        $familyAttributeRelationTable->addColumn(
            'created_at',
            Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Varien_Db_Ddl_Table::TIMESTAMP_INIT],
            'Creation Time'
        );

        $adapter->createTable($familyAttributeRelationTable);

        return $this;
    }

    /**
     * Drop pimgento_api/family_attribute_relations table
     *
     * @return Pimgento_Api_Model_Resource_Family_Attribute_Relations
     */
    public function dropMainTable()
    {
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->_getWriteAdapter();
        /** @var string $familyAttributeRelationTableName */
        $familyAttributeRelationTableName = $this->getMainTable();

        $adapter->dropTable($familyAttributeRelationTableName);

        return $this;
    }

    /**
     * Insert families attribute relations from temporary table
     *
     * @param string $temporaryTableName
     *
     * @return int|false
     * @throws Zend_Db_Statement_Exception
     */
    public function insertFamiliesAttributeRelations($temporaryTableName)
    {
        if (empty($temporaryTableName)) {
            return false;
        }
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->_getWriteAdapter();
        /** @var string $familyAttributeRelationsTable */
        $familyAttributeRelationsTable = $this->getMainTable();
        /** @var Pimgento_Api_Helper_Configuration $configurationHelper */
        $configurationHelper = Mage::helper('pimgento_api/configuration');
        /** @var bool $isPrefixEnabled */
        $isPrefixEnabled = $configurationHelper->isPrefixEnabled();

        /** @var string[] $values */
        $values = [
            'family_entity_id' => '_entity_id',
            'attribute_code'   => 'attributes',
        ];
        $adapter->delete($familyAttributeRelationsTable);
        /** @var Varien_Db_Select $relations */
        $relations = $adapter->select()->from($temporaryTableName, $values);
        /** @var \Zend_Db_Statement_Interface $query */
        $query = $adapter->query($relations);
        /** @var int $attributeRelationsCount */
        $attributeRelationsCount = 0;
        /** @var string[] $row */
        while ($row = $query->fetch()) {
            $attributeRelationsCount += $this->insertFamilyAttributeRelations($row, $isPrefixEnabled);
        }

        return $attributeRelationsCount;
    }

    /**
     * Insert single family attribute relations from temporary table
     *
     * @param string[] $row
     * @param bool     $isPrefixEnabled
     *
     * @return int|false
     */
    public function insertFamilyAttributeRelations($row, $isPrefixEnabled)
    {
        if (empty($row['attribute_code']) || empty($row['family_entity_id'])) {
            return false;
        }
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->_getWriteAdapter();
        /** @var string $familyAttributeRelationsTable */
        $familyAttributeRelationsTable = $this->getMainTable();
        /** @var Pimgento_Api_Helper_Attribute $attributeHelper */
        $attributeHelper = Mage::helper('pimgento_api/attribute');
        /** @var string[] $attributes */
        $attributes = explode(',', $row['attribute_code']);
        /** @var int $attributeRelationsCount */
        $attributeRelationsCount = 0;
        /** @var string $attributeCode */
        foreach ($attributes as $attributeCode) {
            if ($isPrefixEnabled === true && $attributeHelper->isAttributeCodeReserved($attributeCode)) {
                $attributeCode = Pimgento_Api_Helper_Attribute::RESERVED_ATTRIBUTE_CODE_PREFIX . $attributeCode;
            }
            /** @var string[] $bind */
            $bind = [
                'family_entity_id' => $row['family_entity_id'],
                'attribute_code'   => $attributeCode,
            ];
            /** @var Zend_Db_Statement_Interface $statement */
            $attributeRelationsCount += $adapter->insert($familyAttributeRelationsTable, $bind);
        }

        return $attributeRelationsCount;
    }
}
