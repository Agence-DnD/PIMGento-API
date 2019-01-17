<?php
/**
 * @category  Setup
 * @package   Pimgento_Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

try {
    $installer->getConnection()->addIndex(
        $installer->getTable('eav_attribute_option_value'),
        $installer->getIdxName(
            'eav_attribute_option_value',
            ['option_id', 'store_id'],
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        ['option_id', 'store_id'],
        Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
    );
} catch (Zend_Db_Exception $exception) {
    /** @var string $exceptionMessage */
    $exceptionMessage = printf('Pimgento table install failure: %s', $exception->getMessage());
    throw new Pimgento_Api_Exception($exceptionMessage, 0, $exception);
}

$installer->endSetup();
