<?php

/**
 * Class Pimgento_Api_Model_Resource_Eav_Attribute_Set
 *
 * @category  Class
 * @package   Pimgento_Api_Model_Resource_Eav_Attribute_Set
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Resource_Eav_Attribute_Set extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Default attribute set id
     *
     * @var int $defaultAttributeSetId
     */
    protected $defaultAttributeSetId;

    /**
     * Magento constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('eav/attribute_set', 'attribute_set_id');
    }

    /**
     * Insert families from temporary table
     *
     * @param string $temporaryTableName
     *
     * @return bool|int
     * @throws Zend_Db_Statement_Exception
     */
    public function insertFamilies($temporaryTableName)
    {
        if (empty($temporaryTableName)) {
            return false;
        }
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->_getWriteAdapter();
        /** @var string $defaultLocale */
        $defaultLocale = Mage::app()->getLocale()->getDefaultLocale();
        /** @var string $label */
        $label = sprintf('labels-%s', $defaultLocale);
        /** @var int $productEntityTypeId */
        $productEntityTypeId = Mage::getSingleton('eav/config')
            ->getEntityType(Mage_Catalog_Model_Product::ENTITY)
            ->getId();
        /** @var Zend_Db_Expr[] $values */
        $values = [
            'attribute_set_id'   => new Zend_Db_Expr('_entity_id'),
            'entity_type_id'     => new Zend_Db_Expr($productEntityTypeId),
            'attribute_set_name' => new Zend_Db_Expr(sprintf('CONCAT("Pim", " ", `%s`)', $label)),
            'sort_order'         => new Zend_Db_Expr(1),
        ];
        /** @var string[] $fields */
        $fields = array_keys($values);
        /** @var Varien_Db_Select $families */
        $families = $adapter->select()->from($temporaryTableName, $values);
        /** @var Varien_Db_Select $insertFromSelect */
        $insertFromSelect = $adapter->insertFromSelect(
            $families,
            $this->getMainTable(),
            $fields,
            Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE
        );
        /** @var Zend_Db_Statement_Interface $statement */
        $statement = $adapter->query($insertFromSelect);
        /** @var int $rowCount */
        $rowCount = $statement->rowCount();

        return $rowCount;
    }

    /**
     * Initialize attribute sets
     *
     * @param string $temporaryTableName
     *
     * @return bool|int
     * @throws Zend_Db_Statement_Exception
     */
    public function initGroup($temporaryTableName)
    {
        if (empty($temporaryTableName)) {
            return false;
        }
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->_getWriteAdapter();
        /** @var Varien_Db_Select $select */
        $select = $adapter->select()->from($temporaryTableName, ['_entity_id'])->where('_is_new = ?', 1);
        /** @var \Zend_Db_Statement_Interface $query */
        $query = $adapter->query($select);
        /** @var int $attributeSetCount */
        $attributeSetCount = 0;
        /** @var string[] $row */
        while (($row = $query->fetch())) {
            /** @var bool $attributeSetInitialized */
            $attributeSetInitialized = $this->initAttributeSet($row['_entity_id']);
            if ($attributeSetInitialized) {
                $attributeSetCount++;
            }
        }

        return $attributeSetCount;
    }

    /**
     * Initialize attribute set
     *
     * @param int $attributeSetId
     *
     * @return bool
     * @throws Exception
     */
    public function initAttributeSet($attributeSetId)
    {
        /** @var Mage_Eav_Model_Entity_Attribute_Set $attributeSet */
        $attributeSet = Mage::getModel('eav/entity_attribute_set');
        $attributeSet->load($attributeSetId);
        if (!$attributeSet->hasData()) {
            return false;
        }
        /** @var string $defaultAttributeSetId */
        $defaultAttributeSetId = $this->getDefaultAttributeSetId();
        $attributeSet->initFromSkeleton($defaultAttributeSetId)->save();

        return true;
    }

    /**
     * Retrieve default attribute set id
     *
     * @return string
     */
    public function getDefaultAttributeSetId()
    {
        if (empty($this->defaultAttributeSetId)) {
            /** @var Mage_Eav_Model_Config $eavConfig */
            $eavConfig = Mage::getSingleton('eav/config');

            $this->defaultAttributeSetId = $eavConfig
                ->getEntityType(Mage_Catalog_Model_Product::ENTITY)
                ->getDefaultAttributeSetId();
        }

        return $this->defaultAttributeSetId;
    }
}
