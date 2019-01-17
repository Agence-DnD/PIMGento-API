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
    /** @var Pimgento_Api_Model_Resource_Entities $entitiesResource */
    $entitiesResource = Mage::getResourceSingleton('pimgento_api/entities');
    $entitiesResource->dropMainTable();
    $entitiesResource->createMainTable();

    /** @var Pimgento_Api_Model_Resource_Family_Attribute_Relations $familyResource */
    $familyResource = Mage::getResourceSingleton('pimgento_api/family_attribute_relations');
    $familyResource->dropMainTable();
    $familyResource->createMainTable();
} catch (Zend_Db_Exception $exception) {
    /** @var string $exceptionMessage */
    $exceptionMessage = printf('Pimgento table install failure: %s', $exception->getMessage());
    throw new Pimgento_Api_Exception($exceptionMessage, 0, $exception);
}

$installer->endSetup();
