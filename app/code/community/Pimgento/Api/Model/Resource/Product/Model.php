<?php

/**
 * Class Pimgento_Api_Model_Resource_Product_Model
 *
 * @category  Class
 * @package   Pimgento_Api_Model_Resource_Product_Model
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Resource_Product_Model extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Pimgento_Api_Model_Resource_Product_Model constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('pimgento_api/product_model', null);
    }

    /**
     * Create pimgento_api/product_model table
     *
     * @return Pimgento_Api_Model_Resource_Product_Model
     * @throws Zend_Db_Exception
     */
    public function createMainTable()
    {
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->_getWriteAdapter();
        /** @var string $productModelTableName */
        $productModelTableName = $this->getMainTable();
        /** @var Varien_Db_Ddl_Table $productModelTable */
        $productModelTable = $adapter->newTable($productModelTableName);

        $productModelTable->setComment('Pimgento Product Model');
        $productModelTable->addColumn(
            'code',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            255,
            ['nullable' => false, 'primary' => true],
            'Code'
        );
        $productModelTable->addColumn(
            'axes',
            Varien_Db_Ddl_Table::TYPE_VARCHAR,
            255,
            ['nullable' => false],
            'Axes'
        );

        $adapter->createTable($productModelTable);

        return $this;
    }

    /**
     * Drop pimgento_api/product_model table
     *
     * @return Pimgento_Api_Model_Resource_Product_Model
     */
    public function dropMainTable()
    {
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $this->_getWriteAdapter();
        /** @var string $productModelTableName */
        $productModelTableName = $this->getMainTable();

        $adapter->dropTable($productModelTableName);

        return $this;
    }
}
