<?php

/**
 * Class Pimgento_Api_Model_Job_Attribute
 *
 * @category  Class
 * @package   Pimgento_Api_Model_Job_Attribute
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://pimgento.com/
 */
class Pimgento_Api_Model_Job_Attribute extends Pimgento_Api_Model_Job_Abstract
{
    /**
     * Pimgento_Api_Model_Job_Family constructor
     */
    public function __construct()
    {
        $this->code             = 'attribute';
        $this->indexerProcesses = [
            'catalog_product_attribute',
            'catalog_product_flat',
        ];
    }

    /**
     * Create temporary table from Api results (Step 1)
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
        /** @var \Akeneo\Pim\ApiClient\AkeneoPimClientInterface|\Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface $client */
        $client = $this->getClient();
        /** @var \Akeneo\Pim\ApiClient\Pagination\PageInterface $attributes */
        $attributes = $client->getAttributeApi()->listPerPage(1);
        /** @var mixed[] $attribute */
        $attribute = $attributes->getItems();
        $attribute = reset($attribute);

        if (empty($attribute)) {
            $task->stop($helper->__('No results retrieved from Akeneo for %s import', $this->getCode()));
        }

        /** @var Pimgento_Api_Helper_Entities $entitiesHelper */
        $entitiesHelper = Mage::helper('pimgento_api/entities');
        /** @var string[] $columns */
        $columns = $entitiesHelper->getColumnNamesFromResult($attribute);

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
     * Insert Api response data into temporary table (Step 2)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Mage_Core_Exception|Pimgento_Api_Exception|Task_Executor_Exception
     */
    public function insertData($task)
    {
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = $this->getHelper();
        /** @var \Akeneo\Pim\ApiClient\AkeneoPimClientInterface|\Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface $client */
        $client = $this->getClient();
        /** @var int $paginationSize */
        $paginationSize = $this->getConfigurationHelper()->getPaginationSize();
        /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursor $attributes */
        $attributes = $client->getAttributeApi()->all($paginationSize);
        /** @var Pimgento_Api_Helper_Entities $entitiesHelper */
        $entitiesHelper = Mage::helper('pimgento_api/entities');
        /** @var Pimgento_Api_Model_Resource_Entities $resourceEntities */
        $resourceEntities = $this->getResourceEntities();
        /** @var bool $isPrefixEnabled */
        $isPrefixEnabled = $this->getConfigurationHelper()->isPrefixEnabled();
        /** @var Pimgento_Api_Helper_Attribute $attributeHelper */
        $attributeHelper = Mage::helper('pimgento_api/attribute');

        /**
         * @var int     $index
         * @var mixed[] $attribute
         */
        foreach ($attributes as $index => $attribute) {
            /** @var string $attributeCode */
            $attributeCode = $attribute['code'];
            /** @var int $id */
            if ($attributeHelper->isAttributeCodeReserved($attributeCode)) {
                if (!$isPrefixEnabled) {
                    $task->setStepWarning(
                        $helper->__('Attribute %s skipped because of reserved Magento code', $attributeCode)
                    );

                    continue;
                }

                $attribute['code'] = Pimgento_Api_Helper_Attribute::RESERVED_ATTRIBUTE_CODE_PREFIX . $attributeCode;
            }
            /** @var string[] $columns */
            $columns = $entitiesHelper->getColumnsFromResult($attribute);
            /** @var bool $result */
            $result = $resourceEntities->insertDataFromApi($columns);
            if (!$result) {
                $task->stop($this->getHelper()->__('Could not insert Attribute data in temp table'));
            }
        }
        if (!isset($index)) {
            $task->stop($this->getHelper()->__('No Attribute data to insert in temp table'));
        }
        $index++;

        $task->setStepMessage($this->getHelper()->__('%d line(s) found', $index));
    }

    /**
     * Match response api codes with magento ids (Step 3)
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
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        /** @var string $entityTableName */
        $entityTableName = 'eav/attribute';

        /** @var string $codeExpr */
        $codeExpr = sprintf("'%s'", $this->getCode());
        /** @var Varien_Db_Select $select */
        $select = $adapter->select()->from(
            $resource->getTableName($entityTableName),
            [
                'import'                                           => new Zend_Db_Expr($codeExpr),
                Pimgento_Api_Model_Resource_Entities::PIM_CODE_KEY => 'attribute_code',
                'entity_id'                                        => 'attribute_id',
            ]
        )->where('entity_type_id = ?', $this->getProductEntityTypeId());

        /** @var string $insert */
        $insert = $adapter->insertFromSelect(
            $select,
            $resourceEntities->getMainTable(),
            ['import', Pimgento_Api_Model_Resource_Entities::PIM_CODE_KEY, 'entity_id'],
            Varien_Db_Adapter_Interface::INSERT_IGNORE
        );

        $adapter->query($insert);

        $resourceEntities->matchEntity(
            Pimgento_Api_Model_Resource_Entities::PIM_CODE_KEY,
            $entityTableName,
            'attribute_id'
        );

        $task->setStepMessage($this->getHelper()->__('Entity matching successful'));
    }

    /**
     * Match type with Magento logic (Step 4)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    public function matchType($task)
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        /** @var string $tableName */
        $tableName = $this->getTableName();
        /** @var Pimgento_Api_Helper_Attribute $attributeHelper */
        $attributeHelper = Mage::helper('pimgento_api/attribute');
        /** @var mixed[] $columns */
        $columns = $attributeHelper->getSpecificColumns();

        /**
         * @var string  $name
         * @var mixed[] $def
         */
        foreach ($columns as $name => $def) {
            $adapter->addColumn($tableName, $name, $def['type']);
        }

        /** @var string[] $columns */
        $columns = array_keys($columns);
        /** @var string[] $fields */
        $fields = array_merge(['_entity_id', 'type'], $columns);
        /** @var Varien_Db_Select $select */
        $select = $adapter->select()->from($tableName, $fields);
        /** @var string[] $data */
        $data = $adapter->fetchAssoc($select);
        /**
         * @var int     $id
         * @var mixed[] $attribute
         */
        foreach ($data as $id => $attribute) {
            /** @var mixed[] $type */
            $type = $attributeHelper->getType($attribute['type']);

            $adapter->update($tableName, $type, ['_entity_id = ?' => $id]);
        }

        $task->setStepMessage($this->getHelper()->__('Entity type matched successfully'));
    }

    /**
     * Match family code with Magento group id (Step 5)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Pimgento_Api_Exception|Zend_Db_Statement_Exception
     */
    public function matchFamily($task)
    {
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        /** @var string $tableName */
        $tableName = $this->getTableName();
        /** @var string $relationsTable */
        $relationsTable = $resource->getTableName('pimgento_api/family_attribute_relations');

        $adapter->addColumn($tableName, '_attribute_set_id', 'TEXT NULL');
        /** @var Varien_Db_Select $select */
        $select = $adapter->select()->from($tableName, ['code', '_entity_id']);
        /** @var Zend_Db_Statement_Interface $query */
        $query = $adapter->query($select);

        /** @var mixed[] $row */
        while ($row = $query->fetch()) {
            /** @var string $attributeCode */
            $attributeCode = $row['code'];
            /** @var string $condition */
            $condition = $adapter->prepareSqlCondition('attribute_code', ['like' => $attributeCode]);
            /** @var Varien_Db_Select $relationsSelect */
            $relationsSelect = $adapter->select()->from($relationsTable, 'family_entity_id')->where($condition);
            /** @var Zend_Db_Statement_Interface $relationsQuery */
            $relationsQuery = $adapter->query($relationsSelect);

            /** @var int[] $attributeIds */
            $attributeIds = [];
            /** @var mixed[] $innerRow */
            while ($innerRow = $relationsQuery->fetch()) {
                if (empty($innerRow['family_entity_id'])) {
                    continue;
                }

                $attributeIds[] = $innerRow['family_entity_id'];
            }

            $attributeIds = array_filter($attributeIds);
            /** @var string $attributeIds */
            $attributeIds = implode(',', $attributeIds);

            $adapter->update(
                $tableName,
                ['_attribute_set_id' => $attributeIds],
                sprintf('_entity_id=%s', $row['_entity_id'])
            );
        }

        $task->setStepMessage($this->getHelper()->__('Family matched successfully'));
    }

    /**
     * Add attributes if not exists (Step 6)
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     * @throws Mage_Core_Exception|Pimgento_Api_Exception|Zend_Db_Statement_Exception
     */
    public function addAttributes($task)
    {
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = $this->getHelper();
        /** @var Pimgento_Api_Helper_Attribute $attributeHelper */
        $attributeHelper = Mage::helper('pimgento_api/attribute');
        /** @var Pimgento_Api_Helper_Store $storeHelper */
        $storeHelper = Mage::helper('pimgento_api/store');
        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        /** @var string $tableName */
        $tableName = $this->getTableName();
        /** @var string $eavAttributeTable */
        $eavAttributeTable = $resource->getTableName('eav_attribute');
        /** @var string $catalogAttributeTable */
        $catalogAttributeTable = $resource->getTableName('catalog_eav_attribute');
        /** @var string $attributeLabelTable */
        $attributeLabelTable = $resource->getTableName('eav_attribute_label');
        /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attributeModel */
        $attributeModel = Mage::getResourceModel('catalog/eav_attribute');
        /** @var Mage_Eav_Model_Entity_Setup $eavSetup */
        $eavSetup = new Mage_Eav_Model_Entity_Setup('core_setup');
        /** @var bool $isPrefixEnabled */
        $isPrefixEnabled = $this->getConfigurationHelper()->isPrefixEnabled();
        /** @var mixed[] $specificColumns */
        $specificColumns = $attributeHelper->getSpecificColumns();

        /** @var string $adminLang */
        $adminLang = $storeHelper->getAdminLang();
        /** @var string $adminLabelColumn */
        $adminLabelColumn = sprintf('labels-%s', $adminLang);

        /** @var Zend_Db_Select $import */
        $import = $adapter->select()->from($tableName);
        /** @var Zend_Db_Statement_Interface $query */
        $query = $adapter->query($import);

        /** @var mixed[] $row */
        while ($row = $query->fetch()) {
            if (empty($row['code']) || empty($row['_entity_id'])) {
                continue;
            }

            /** @var string $attributeCode */
            $attributeCode = $row['code'];
            if ($attributeHelper->isAttributeCodeReserved($attributeCode)) {
                if (!$isPrefixEnabled) {
                    $task->setStepWarning(
                        $helper->__('Attribute %s skipped because of reserved Magento code', $attributeCode)
                    );

                    continue;
                }

                $attributeCode = Pimgento_Api_Helper_Attribute::RESERVED_ATTRIBUTE_CODE_PREFIX . $attributeCode;
            }

            /** @var string $attributeCodeExpr */
            $attributeCodeExpr = sprintf("'%s'", $attributeCode);
            /** @var mixed[] $values */
            $values = [
                'attribute_id'   => new Zend_Db_Expr($row['_entity_id']),
                'entity_type_id' => new Zend_Db_Expr($this->getProductEntityTypeId()),
                'attribute_code' => new Zend_Db_Expr($attributeCodeExpr),
            ];

            /* Insert base data (ignore if already exists) */
            $adapter->insertIgnore($eavAttributeTable, $values);

            /* Retrieve attribute scope */
            /** @var int $global */
            $global = Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL; // Global
            if (!empty($row['scopable']) && $row['scopable'] === '1') {
                $global = Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE; // Website
            }
            if (!empty($row['localizable']) && $row['localizable'] === '1') {
                $global = Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE; // Store View
            }

            /** @var mixed[] $values */
            $values = [
                'attribute_id' => new Zend_Db_Expr($row['_entity_id']),
                'is_global'    => new Zend_Db_Expr($global),
            ];

            $adapter->insertOnDuplicate($catalogAttributeTable, $values, array_keys($values));

            /* Retrieve default admin label */
            /** @var string $frontendLabel */
            $frontendLabel = $storeHelper->__('Unknown');
            if (!empty($row[$adminLabelColumn])) {
                $frontendLabel = $row[$adminLabelColumn];
            }

            /** @var mixed[] $data */
            $data = [
                'entity_type_id' => $this->getProductEntityTypeId(),
                'frontend_label' => $frontendLabel,
                'is_global'      => $global,
                'is_unique'      => $row['unique'],
            ];

            /**
             * @var string  $column
             * @var mixed[] $feature
             */
            foreach ($specificColumns as $column => $feature) {
                if (!$feature['only_init']) {
                    $data[$column] = $row[$column];
                }
            }
            /** @var mixed[] $defaultValues */
            $defaultValues = [];
            if ($row['_is_new'] === '1') {
                $defaultValues = [
                    'attribute_code'                => $row['code'],
                    'attribute_id'                  => $row['_entity_id'],
                    'attribute_model'               => null,
                    'backend_model'                 => $row['backend_model'],
                    'backend_type'                  => $row['backend_type'],
                    'frontend_input'                => $row['frontend_input'],
                    'frontend_model'                => null,
                    'is_configurable'               => 0,
                    'is_used_for_pricerules'        => 0,
                    'source_model'                  => $row['source_model'],
                    'backend_table'                 => null,
                    'frontend_class'                => null,
                    'is_required'                   => 0,
                    'is_user_defined'               => 1,
                    'default_value'                 => null,
                    'is_unique'                     => $row['unique'],
                    'note'                          => null,
                    'is_visible'                    => 1,
                    'frontend_input_renderer'       => null,
                    'is_searchable'                 => 0,
                    'is_filterable'                 => 0,
                    'is_comparable'                 => 0,
                    'is_visible_on_front'           => 0,
                    'is_wysiwyg_enabled'            => 0,
                    'is_html_allowed_on_front'      => 0,
                    'is_visible_in_advanced_search' => 0,
                    'is_filterable_in_search'       => 0,
                    'used_in_product_listing'       => 0,
                    'used_for_sort_by'              => 0,
                    'apply_to'                      => null,
                    'position'                      => 0,
                    'is_used_for_promo_rules'       => 0,
                ];

                /** @var string[] $columns */
                $columns = array_keys($specificColumns);
                foreach ($columns as $column) {
                    $data[$column] = $row[$column];
                }
            }

            /** @var mixed[] $data */
            $data = array_merge($defaultValues, $data);

            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
            $attribute = $attributeModel->load($row['_entity_id']);
            $attribute->addData($data);
            $attribute->save();

            /* Add store labels */
            /** @var mixed[] $stores */
            $stores = $storeHelper->getStores('lang');
            /**
             * @var string  $lang
             * @var mixed[] $data
             */
            foreach ($stores as $lang => $data) {
                /** @var string $labelColumn */
                $labelColumn = sprintf('labels-%s', $lang);
                if (empty($row[$labelColumn])) {
                    $task->setStepWarning(
                        $this->getHelper()->__('No label %s set for attribute %s', $labelColumn, $row['code'])
                    );

                    continue;
                }

                /** @var string $label */
                $label = $row[$labelColumn];
                /** @var mixed[] $store */
                foreach ($data as $store) {
                    /** @var int $storeId */
                    $storeId = $store['store_id'];
                    /** @var int $entityId */
                    $entityId = $row['_entity_id'];
                    /** @var Varien_Db_Select $select */
                    $select = $adapter->select()->from($attributeLabelTable)->where('attribute_id = ?', $entityId)->where('store_id = ?', $storeId);
                    /** @var string[] $values */
                    $values = ['value' => $label];
                    /** @var string $exists */
                    $exists = $adapter->fetchOne($select);
                    if (!empty($exists)) {
                        /** @var string[] $where */
                        $where = ['attribute_id = ?' => $entityId, 'store_id = ?' => $storeId];

                        $adapter->update($attributeLabelTable, $values, $where);

                        continue;
                    }

                    /** @var mixed[] $values */
                    $values = array_merge(
                        $values,
                        [
                            'attribute_id' => $entityId,
                            'store_id'     => $storeId,
                        ]
                    );

                    $adapter->insert($attributeLabelTable, $values);
                }
            }

            /* Add Attribute to group and family */
            if (empty($row['_attribute_set_id']) || empty($row['group'])) {
                continue;
            }

            /** @var int[] $attributeSetIds */
            $attributeSetIds = explode(',', $row['_attribute_set_id']);

            if (is_numeric($row['group'])) {
                $row['group'] = sprintf('PIM%s', $row['group']);
            }

            /** @var int $attributeSetId */
            foreach ($attributeSetIds as $attributeSetId) {
                if (!is_numeric($attributeSetId)) {
                    continue;
                }

                /** @var string $groupName */
                $groupName = ucfirst($row['group']);
                $eavSetup->addAttributeGroup(Mage_Catalog_Model_Product::ENTITY, $attributeSetId, $groupName);
                /** @var int $id */
                $id = $eavSetup->getAttributeGroupId(Mage_Catalog_Model_Product::ENTITY, $attributeSetId, $groupName);
                if (empty($id)) {
                    continue;
                }

                $eavSetup->addAttributeToSet(
                    Mage_Catalog_Model_Product::ENTITY,
                    $attributeSetId,
                    $id,
                    $attributeModel->getId()
                );
            }
        }

        $task->setStepMessage($helper->__('Attributes created/updated successfully.'));
    }

    /**
     * Drop temporary table (Step 7)
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
     * Retrieve Product Entity type id
     *
     * @return int
     */
    public function getProductEntityTypeId()
    {
        /** @var Pimgento_Api_Helper_Entities $entitiesHelper */
        $entitiesHelper = Mage::helper('pimgento_api/entities');
        /** @var string $productEntityTypeId */
        $productEntityTypeId = $entitiesHelper->getProductEntityTypeId();

        return $productEntityTypeId;
    }
}
