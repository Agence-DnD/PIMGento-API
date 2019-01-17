<?php

/**
 * Class Pimgento_Api_Model_Job_Family
 *
 * @category  Class
 * @package   Pimgento_Api_Model_Job_Family
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Job_Family extends Pimgento_Api_Model_Job_Abstract
{
    /**
     * Pimgento_Api_Model_Job_Family constructor
     */
    public function __construct()
    {
        $this->code = 'family';
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
        /** @var \Akeneo\Pim\ApiClient\Pagination\PageInterface $familyCollection */
        $familyCollection = $client->getFamilyApi()->listPerPage(1);
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = $this->getHelper();
        if ($familyCollection->getCount() === 0) {
            $task->stop($helper->__('No results retrieved from Akeneo'));
        }
        /** @var mixed[] $familyItems */
        $familyItems = $familyCollection->getItems();
        /** @var mixed[] $family */
        $family = reset($familyItems);
        /** @var Pimgento_Api_Helper_Entities $entitiesHelper */
        $entitiesHelper = Mage::helper('pimgento_api/entities');
        /** @var string[] $columns */
        $columns = $entitiesHelper->getColumnNamesFromResult($family);

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
        /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursor $familyItems */
        $familyItems = $client->getFamilyApi()->all($paginationSize);
        /** @var Pimgento_Api_Helper_Entities $entitiesHelper */
        $entitiesHelper = Mage::helper('pimgento_api/entities');
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /**
         * @var int     $index
         * @var mixed[] $family
         */
        foreach ($familyItems as $index => $family) {
            /** @var string[] $columns */
            $columns = $entitiesHelper->getColumnsFromResult($family);
            /** @var bool $result */
            $result = $resourceEntities->insertDataFromApi($columns);
            if (!$result) {
                $task->stop($this->getHelper()->__('Could not insert Family data in temp table'));
            }
        }
        if (!isset($index)) {
            $task->stop($this->getHelper()->__('No Family data to insert in temp table'));
        }
        $index++;

        $task->setStepMessage($this->getHelper()->__('%d line(s) found', $index));
    }

    /**
     * Match response api codes with magento ids
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception|Zend_Db_Statement_Exception
     */
    public function matchEntities($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var string $entityTableName */
        $entityTableName = 'eav/attribute_set';
        /** @var string $primaryKey */
        $primaryKey = 'attribute_set_id';
        $resourceEntities->matchEntity(
            Pimgento_Api_Model_Resource_Entities::PIM_CODE_KEY,
            $entityTableName,
            $primaryKey
        );

        $task->setStepMessage($this->getHelper()->__('Entity matching successful'));
    }

    /**
     * Insert families
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception|Task_Executor_Exception|Zend_Db_Statement_Exception
     */
    public function insertFamilies($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var string $temporaryTableName */
        $temporaryTableName = $resourceEntities->getTableName();
        /** @var Pimgento_Api_Model_Resource_Eav_Attribute_Set $resourceEavAttributeSet */
        $resourceEavAttributeSet = Mage::getResourceSingleton('pimgento_api/eav_attribute_set');
        /** @var int $familiesCount */
        $familiesCount = $resourceEavAttributeSet->insertFamilies($temporaryTableName);
        if ($familiesCount === false) {
            $task->stop($this->getHelper()->__('Could not add families'));
        }

        $task->setStepMessage($this->getHelper()->__('%d family(ies) inserted', $familiesCount));
    }

    /**
     * Insert relations between family and list of attributes
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception|Task_Executor_Exception|Zend_Db_Statement_Exception
     */
    public function insertFamiliesAttributeRelations($task)
    {
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var string $temporaryTableName */
        $temporaryTableName = $resourceEntities->getTableName();
        /** @var Pimgento_Api_Model_Resource_Family_Attribute_Relations $resourceFamilyAttributeRelations */
        $resourceFamilyAttributeRelations = Mage::getResourceSingleton('pimgento_api/family_attribute_relations');
        /** @var int $familiesAttributeRelationsCount */
        $familiesAttributeRelationsCount = $resourceFamilyAttributeRelations->insertFamiliesAttributeRelations($temporaryTableName);
        if ($familiesAttributeRelationsCount === false) {
            $task->stop($this->getHelper()->__('Could not add family attribute relations'));
        }

        $task->setStepMessage($this->getHelper()->__('%d family(ies) attribute relation(s) inserted', $familiesAttributeRelationsCount));
    }

    /**
     * Initialize attribute sets
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception|Task_Executor_Exception|Zend_Db_Statement_Exception
     */
    public function initGroup($task)
    {
        /** @var Pimgento_Api_Model_Resource_Eav_Attribute_Set $resourceEavAttributeSet */
        $resourceEavAttributeSet = Mage::getResourceSingleton('pimgento_api/eav_attribute_set');
        /** @var int $attributeSetCount */
        $attributeSetCount = $resourceEavAttributeSet->initGroup($this->getTableName());
        if ($attributeSetCount === false) {
            $task->stop($this->getHelper()->__('Could not initialize attribute sets'));
        }

        $task->setStepMessage($this->getHelper()->__('%d family(ies) initialized', $attributeSetCount));
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
