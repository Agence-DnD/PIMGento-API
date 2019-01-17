<?php

/**
 * Class Pimgento_Api_Model_Job_Family_Variant
 *
 * @category  Class
 * @package   Pimgento_Api_Model_Job_Family_Variant
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Job_Family_Variant extends Pimgento_Api_Model_Job_Abstract
{
    /**
     * Pimgento_Api_Model_Job_Category constructor
     */
    public function __construct()
    {
        $this->code = 'family_variant';
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
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = $this->getHelper();
        /** @var Akeneo\Pim\ApiClient\AkeneoPimClientInterface|Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface $client */
        $client = $this->getClient();
        /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface $families */
        $families = $client->getFamilyApi()->all();
        /** @var Akeneo\Pim\ApiClient\Api\FamilyVariantApiInterface $variantApi */
        $variantApi = $client->getFamilyVariantApi();

        /** @var mixed[] $familyVariantItem */
        $familyVariantItem = [];
        /** @var mixed[] $family */
        foreach ($families as $family) {
            /** @var Akeneo\Pim\ApiClient\Pagination\PageInterface $familyVariant */
            $familyVariant = $variantApi->listPerPage($family['code'], 1);
            if (empty($familyVariant->getItems())) {
                continue;
            }

            $familyVariantItem = $familyVariant->getItems();

            break;
        }

        if (empty($familyVariantItem)) {
            $task->stop($helper->__('Could not find family variant in Akeneo'));
        }

        $familyVariantItem = reset($familyVariantItem);

        /** @var Pimgento_Api_Helper_Family_Variant $variantHelper */
        $variantHelper = Mage::helper('pimgento_api/family_variant');
        /** @var string[] $columnNames */
        $columnNames = $variantHelper->getColumnNamesFromResult($familyVariantItem);

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
    public function insertData($task)
    {
        /** @var Akeneo\Pim\ApiClient\AkeneoPimClientInterface|Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface $client */
        $client = $this->getClient();
        /** @var int $paginationSize */
        $paginationSize = $this->getConfigurationHelper()->getPaginationSize();
        /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface $families */
        $families = $client->getFamilyApi()->all($paginationSize);

        /** @var int $count */
        $count = 0;
        /**
         * @var int     $index
         * @var mixed[] $families
         */
        foreach ($families as $index => $family) {
            /** @var string $familyCode */
            $familyCode = $family['code'];
            /** @var int|bool $result */
            $result = $this->insertFamilyVariantData($familyCode, $paginationSize);
            if ($result === false) {
                $task->stop($this->getHelper()->__('Could not insert Family Variant data in temp table'));
            }
            $count += $result;
        }
        if ($count === 0) {
            $task->stop($this->getHelper()->__('No Option data to insert in temp table'));
        }

        $task->setStepMessage($this->getHelper()->__('%d line(s) found', $count));
    }

    /**
     * Insert data into TemporaryTable (Step 3)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Mage_Core_Exception|Zend_Db_Statement_Exception
     */
    public function updateAxes($task)
    {
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = $this->getHelper();
        /** @var string $tableName */
        $tableName = $this->getTableName();
        /** @var Pimgento_Api_Helper_Entities $entitiesHelper */
        $entitiesHelper = Mage::helper('pimgento_api/entities');
        /** @var int $productEntityId */
        $productEntityId = $entitiesHelper->getProductEntityTypeId();
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);

        $adapter->addColumn($tableName, 'axes', Varien_Db_Ddl_Table::TYPE_TEXT);

        /** @var string[] $columns */
        $columns = [];
        /** @var int $i */
        for ($i = 1; $i <= Pimgento_Api_Helper_Family_Variant::MAX_AXES_NUMBER; $i++) {
            /** @var string $columnName */
            $columnName = sprintf('variant-axes_%s', $i);
            if ($adapter->tableColumnExists($tableName, $columnName)) {
                $columns[] = $columnName;
            }
        }

        if (!empty($columns)) {
            /** @var string $axesColumns */
            $axesColumns = implode('`, `', $columns);
            /** @var string $updateExpression */
            $updateExpression = sprintf('CONCAT_WS(",", `%s`)', $axesColumns);

            $adapter->update($tableName, ['axes' => new Zend_Db_Expr($updateExpression)]);
        }

        /** @var Zend_Db_Statement_Interface $variantFamily */
        $variantFamily = $adapter->query($adapter->select()->from($tableName));
        /** @var string $eavTable */
        $eavTable = $resource->getTableName('eav/attribute');

        /** @var mixed[] $attributes */
        $attributes = $adapter->fetchPairs(
            $adapter->select()->from(
                $eavTable,
                [
                    'attribute_code',
                    'attribute_id',
                ]
            )->where('entity_type_id = ?', $productEntityId)
        );

        while (($row = $variantFamily->fetch())) {
            if (empty($row['code'])) {
                continue;
            }
            if (empty($row['axes'])) {
                $task->setStepWarning($helper->__('No axes for code: %s', $row['code']));

                continue;
            }

            /** @var  $axesAttributes */
            $axesAttributes = explode(',', $row['axes']);
            /** @var string[] $axes */
            $axes = [];

            /** @var string $axesAttribute */
            foreach ($axesAttributes as $axesAttribute) {
                if (empty($attributes[$axesAttribute])) {
                    $task->setStepWarning($helper->__('No Magento attribute matches axis: %s', $axesAttribute));

                    continue;
                }

                $axes[] = $attributes[$axesAttribute];

                /** @var Mage_Eav_Model_Attribute $attributeModel */
                $attributeModel = Mage::getModel('eav/entity_attribute');
                $attributeModel->loadByCode($productEntityId, $axesAttribute);
                if (!$attributeModel->hasData()) {
                    $task->setStepWarning($helper->__('No eav entity attribute set for axis: %s', $axesAttribute));

                    continue;
                }

                $attributeModel->setData('is_configurable', 1);
                $attributeModel->save();
            }

            /** @var string $axesUpdate */
            $axesUpdate = implode(',', $axes);

            $adapter->update($tableName, ['axes' => $axesUpdate], ['code = ?' => $row['code']]);
        }

        $task->setStepMessage($helper->__('Variant Axes updated'));
    }

    /**
     * Update Product model (Step 4)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    public function updateProductModel($task)
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        /** @var string $tableName */
        $tableName = $this->getTableName();
        /** @var string $variantTable */
        $variantTable = $resource->getTableName('pimgento_api/product_model');

        /** @var Varien_Db_Select $query */
        $query = $adapter->select()->from(false, ['axes' => 'f.axes'])->joinLeft(
            ['f' => $tableName],
            'p.family_variant = f.code',
            []
        );
        /** @var string $update */
        $update = $adapter->updateFromSelect($query, ['p' => $variantTable]);
        $adapter->query($update);

        $task->setStepMessage($this->getHelper()->__('Product Model Axes updated'));
    }

    /**
     * Drop table (Step 5)
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
     * Insert each Family Variant data in temporary table
     *
     * @param string $familyCode
     * @param int    $paginationSize
     *
     * @return int
     * @throws Mage_Core_Exception|Pimgento_Api_Exception
     */
    protected function insertFamilyVariantData($familyCode, $paginationSize)
    {
        /** @var Pimgento_Api_Helper_Family_Variant $variantHelper */
        $variantHelper = Mage::helper('pimgento_api/family_variant');
        /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface $familyVariants */
        $familyVariants = $this->getClient()->getFamilyVariantApi()->all($familyCode, $paginationSize);

        /**
         * @var int $index
         * @var mixed[] $variant
         */
        foreach ($familyVariants as $index => $variant) {
            /** @var string[] $columns */
            $columns = $variantHelper->getColumnsFromResult($variant);
            $this->getResourceEntities()->insertDataFromApi($columns);
        }

        if (!isset($index)) {
            return 0;
        }
        $index++;

        return $index;
    }
}
