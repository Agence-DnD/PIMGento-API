<?php

/**
 * Class Pimgento_Api_Model_Observer
 *
 * @category  Class
 * @package   Pimgento_Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Observer
{
    /**
     * Trigger for pimgento_api_task_executor_load_task event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Pimgento_Api_Model_Observer
     */
    public function taskExecutorLoadTask(Varien_Event_Observer $observer)
    {
        /** @var Task_Executor_Model_Task $task */
        $task = $observer->getEvent()->getTask();

        $this->category($task);
        $this->family($task);
        $this->attribute($task);
        $this->option($task);
        $this->productModel($task);
        $this->familyVariant($task);
        $this->product($task);

        return $this;
    }

    /**
     * Retrieve Helper
     *
     * @return Pimgento_Api_Helper_Data
     */
    protected function getHelper()
    {
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = Mage::helper('pimgento_api');

        return $helper;
    }

    /**
     * Add Category Import
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     */
    protected function category($task)
    {
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = $this->getHelper();

        $task->addTask(
            'category',
            [
                'label'   => $helper->__('Category'),
                'type'    => 'button',
                'comment' => $helper->__('Import category from PIM.'),
                'options' => [],
                'steps'   => [
                    1  => [
                        'comment' => $helper->__('Create temporary table'),
                        'method'  => 'pimgento_api/job_category::createTable',
                    ],
                    2  => [
                        'comment' => $helper->__('Fill data into temporary table'),
                        'method'  => 'pimgento_api/job_category::insertData',
                    ],
                    3  => [
                        'comment' => $helper->__('Match PIM code with Magento entity ID'),
                        'method'  => 'pimgento_api/job_category::matchEntity',
                    ],
                    4  => [
                        'comment' => $helper->__('Create URL key'),
                        'method'  => 'pimgento_api/job_category::prepareUrlKey',
                    ],
                    5  => [
                        'comment' => $helper->__('Prepare categories level'),
                        'method'  => 'pimgento_api/job_category::setLevel',
                    ],
                    6  => [
                        'comment' => $helper->__('Remove Categories By Filter'),
                        'method'  => 'pimgento_api/job_category::removeCategoriesByFilter',
                    ],
                    7  => [
                        'comment' => $helper->__('Prepare categories position'),
                        'method'  => 'pimgento_api/job_category::setPosition',
                    ],
                    8  => [
                        'comment' => $helper->__('Create and update category entities'),
                        'method'  => 'pimgento_api/job_category::createEntities',
                    ],
                    9  => [
                        'comment' => $helper->__('Set values to attributes'),
                        'method'  => 'pimgento_api/job_category::setValues',
                    ],
                    10  => [
                        'comment' => $helper->__('Child categories count'),
                        'method'  => 'pimgento_api/job_category::updateChildrenCount',
                    ],
                    11 => [
                        'comment' => $helper->__('Update URL keys'),
                        'method'  => 'pimgento_api/job_category::setUrlKey',
                    ],
                    12 => [
                        'comment' => $helper->__('Drop temporary table'),
                        'method'  => 'pimgento_api/job_category::dropTable',
                    ],
                    13 => [
                        'comment' => $helper->__('Reindex Data'),
                        'method'  => 'pimgento_api/job_category::reindex',
                    ],
                    14 => [
                        'comment' => $helper->__('Clear cache'),
                        'method'  => 'pimgento_api/job_category::cleanCache',
                    ],
                ],
            ]
        );
    }

    /**
     * Wrapper for catalog_category_delete_after event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function catalogCategoryDeleteAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Category $category */
        $category = $observer->getEvent()->getCategory();

        $this->deleteCategoryCode($category);
    }

    /**
     * Delete category code
     *
     * @param Mage_Catalog_Model_Category $category
     *
     * @return void
     */
    protected function deleteCategoryCode(Mage_Catalog_Model_Category $category)
    {
        if (!$category->getId()) {
            return;
        }
        /** @var Pimgento_Api_Model_Entities $entity */
        $entity = Mage::getModel('pimgento_api/entities');
        /** @var Pimgento_Api_Model_Job_Category $import */
        $import = Mage::getSingleton('pimgento_api/job_category');

        $entity->deleteByEntityId($import->getCode(), $category->getId());
    }

    /**
     * Add Family Import
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     */
    protected function family($task)
    {
        $task->addTask(
            'family',
            [
                'label'   => $this->getHelper()->__('Family'),
                'type'    => 'button',
                'comment' => $this->getHelper()->__('Import family from PIM.'),
                'options' => [],
                'steps'   => [
                    1 => [
                        'comment' => $this->getHelper()->__('Create temporary table'),
                        'method'  => 'pimgento_api/job_family::createTable',
                    ],
                    2 => [
                        'comment' => $this->getHelper()->__('Fill data into temporary table'),
                        'method'  => 'pimgento_api/job_family::insertData',
                    ],
                    3 => [
                        'comment' => $this->getHelper()->__('Match PIM code with entity'),
                        'method'  => 'pimgento_api/job_family::matchEntities',
                    ],
                    4 => [
                        'comment' => $this->getHelper()->__('Create Families'),
                        'method'  => 'pimgento_api/job_family::insertFamilies',
                    ],
                    5 => [
                        'comment' => $this->getHelper()->__('Create family attribute relations'),
                        'method'  => 'pimgento_api/job_family::insertFamiliesAttributeRelations',
                    ],
                    6 => [
                        'comment' => $this->getHelper()->__('Init default groups'),
                        'method'  => 'pimgento_api/job_family::initGroup',
                    ],
                    7 => [
                        'comment' => $this->getHelper()->__('Drop temporary table'),
                        'method'  => 'pimgento_api/job_family::dropTable',
                    ],
                ],
            ]
        );
    }

    /**
     * Wrapper for eav_entity_attribute_set_delete_after event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function eavEntityAttributeSetDeleteAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Eav_Model_Entity_Attribute_Set $attributeSet */
        $attributeSet = $observer->getEvent()->getObject();

        $this->deleteFamilyCode($attributeSet);
    }

    /**
     * Delete family code
     *
     * @param Mage_Eav_Model_Entity_Attribute_Set $attributeSet
     *
     * @return void
     */
    protected function deleteFamilyCode(Mage_Eav_Model_Entity_Attribute_Set $attributeSet)
    {
        if (!$attributeSet->getId()) {
            return;
        }

        /** @var Pimgento_Api_Model_Entities $entity */
        $entity = Mage::getModel('pimgento_api/entities');
        /** @var Pimgento_Api_Model_Job_Family $import */
        $import = Mage::getSingleton('pimgento_api/job_family');

        $entity->deleteByEntityId($import->getCode(), $attributeSet->getId());
    }

    /**
     * Add Attribute Import
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     */
    protected function attribute($task)
    {
        $task->addTask(
            'attribute',
            [
                'label'   => $this->getHelper()->__('Attribute'),
                'type'    => 'button',
                'comment' => $this->getHelper()->__('Import attribute from PIM.'),
                'options' => [],
                'steps'   => [
                    1 => [
                        'comment' => $this->getHelper()->__('Create temporary table'),
                        'method'  => 'pimgento_api/job_attribute::createTable',
                    ],
                    2 => [
                        'comment' => $this->getHelper()->__('Fill data into temporary table'),
                        'method'  => 'pimgento_api/job_attribute::insertData',
                    ],
                    3 => [
                        'comment' => $this->getHelper()->__('Match PIM code with Magento entity ID'),
                        'method'  => 'pimgento_api/job_attribute::matchEntities',
                    ],
                    4 => [
                        'comment' => $this->getHelper()->__('Match PIM type with Magento logic'),
                        'method'  => 'pimgento_api/job_attribute::matchType',
                    ],
                    5 => [
                        'comment' => $this->getHelper()->__('Match PIM family code with Magento group id'),
                        'method'  => 'pimgento_api/job_attribute::matchFamily',
                    ],
                    6 => [
                        'comment' => $this->getHelper()->__('Add or update attributes'),
                        'method'  => 'pimgento_api/job_attribute::addAttributes',
                    ],
                    7 => [
                        'comment' => $this->getHelper()->__('Drop temporary table'),
                        'method'  => 'pimgento_api/job_attribute::dropTable',
                    ],
                    8 => [
                        'comment' => $this->getHelper()->__('Reindex Data'),
                        'method'  => 'pimgento_api/job_attribute::reindex',
                    ],
                    9 => [
                        'comment' => $this->getHelper()->__('Clear cache'),
                        'method'  => 'pimgento_api/job_attribute::cleanCache',
                    ],
                ],
            ]
        );
    }

    /**
     * Wrapper for catalog_entity_attribute_delete_after event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function catalogEntityAttributeDeleteAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Eav_Model_Entity_Attribute $attribute */
        $attribute = $observer->getEvent()->getAttribute();
        if (!$attribute instanceof Mage_Catalog_Model_Resource_Eav_Attribute) {
            return;
        }

        $this->deleteAttributeCode($attribute);
    }

    /**
     * Delete attribute and option codes
     *
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     *
     * @return void
     */
    protected function deleteAttributeCode(Mage_Catalog_Model_Resource_Eav_Attribute $attribute)
    {
        if (!$attribute->getId()) {
            return;
        }
        /** @var Pimgento_Api_Model_Entities $entity */
        $entity = Mage::getModel('pimgento_api/entities');
        /** @var Pimgento_Api_Model_Job_Attribute $import */
        $attributeImport = Mage::getSingleton('pimgento_api/job_attribute');

        $entity->deleteByEntityId($attributeImport->getCode(), $attribute->getId());
    }

    /**
     * Add attributes options import
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     */
    protected function option(Task_Executor_Model_Task $task)
    {
        $task->addTask(
            'option',
            [
                'label'   => $this->getHelper()->__('Attribute Options'),
                'type'    => 'button',
                'comment' => $this->getHelper()->__('Import attribute options'),
                'steps'   => [
                    1 => [
                        'comment' => $this->getHelper()->__('Create temporary table'),
                        'method'  => 'pimgento_api/job_option::createTable',
                    ],
                    2 => [
                        'comment' => $this->getHelper()->__('Fill data into temporary table'),
                        'method'  => 'pimgento_api/job_option::insertData',
                    ],
                    3 => [
                        'comment' => $this->getHelper()->__('Match PIM code with Magento entity ID'),
                        'method'  => 'pimgento_api/job_option::matchEntity',
                    ],
                    4 => [
                        'comment' => $this->getHelper()->__('Associate options to attributes'),
                        'method'  => 'pimgento_api/job_option::insertOptions',
                    ],
                    5 => [
                        'comment' => $this->getHelper()->__('Associate values to options'),
                        'method'  => 'pimgento_api/job_option::insertValues',
                    ],
                    6 => [
                        'comment' => $this->getHelper()->__('Drop temporary table'),
                        'method'  => 'pimgento_api/job_option::dropTable',
                    ],
                    7 => [
                        'comment' => $this->getHelper()->__('Reindex Data'),
                        'method'  => 'pimgento_api/job_option::reindex',
                    ],
                    8 => [
                        'comment' => $this->getHelper()->__('Clear cache'),
                        'method'  => 'pimgento_api/job_option::cleanCache',
                    ],
                ],
            ]
        );
    }

    /**
     * Wrapper for catalog_entity_attribute_delete_before event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function catalogEntityAttributeDeleteBefore(Varien_Event_Observer $observer)
    {
        /** @var Mage_Eav_Model_Entity_Attribute $attribute */
        $attribute = $observer->getEvent()->getAttribute();
        if (!$attribute instanceof Mage_Catalog_Model_Resource_Eav_Attribute) {
            return;
        }

        $this->deleteAttributeOptionsCodes($attribute);
    }

    /**
     * Delete attribute options codes
     *
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     *
     * @return void
     */
    protected function deleteAttributeOptionsCodes(Mage_Catalog_Model_Resource_Eav_Attribute $attribute)
    {
        if (!$attribute->getId()) {
            return;
        }
        /** @var Pimgento_Api_Model_Entities $entity */
        $entity = Mage::getModel('pimgento_api/entities');
        /** @var Pimgento_Api_Model_Job_Option $optionImport */
        $optionImport = Mage::getSingleton('pimgento_api/job_option');
        /** @var string $optionCode */
        $optionCode = $optionImport->getCode();

        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Option $model */
        $resource = Mage::getResourceModel('eav/entity_attribute_option');
        /** @var Varien_Db_Adapter_Interface $adapter */
        $adapter = $resource->getReadConnection();
        /** @var Varien_Db_Select $select */
        $select = $adapter->select()->from($resource->getMainTable(), ['option_id'])->where(
            'attribute_id = ?',
            (int)$attribute->getId()
        );

        /** @var string[] $optionIds */
        $optionIds = $adapter->fetchAll($select);
        foreach ($optionIds as $optionId) {
            $entity->deleteByEntityId($optionCode, $optionId);
        }
    }

    /**
     * Add Product Model Import
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     */
    protected function productModel($task)
    {
        $task->addTask(
            'product_model',
            [
                'label'   => $this->getHelper()->__('Product Model'),
                'type'    => 'button',
                'comment' => $this->getHelper()->__('Import Product Model from PIM.'),
                'options' => [],
                'steps'   => [
                    1 => [
                        'comment' => $this->getHelper()->__('Create temporary table'),
                        'method'  => 'pimgento_api/job_product_model::createTable',
                    ],
                    2 => [
                        'comment' => $this->getHelper()->__('Fill temporary table'),
                        'method'  => 'pimgento_api/job_product_model::insertData',
                    ],
                    3 => [
                        'comment' => $this->getHelper()->__('Remove columns from product model table'),
                        'method'  => 'pimgento_api/job_product_model::removeColumns',
                    ],
                    4 => [
                        'comment' => $this->getHelper()->__('Add columns to product model table'),
                        'method'  => 'pimgento_api/job_product_model::addColumns',
                    ],
                    5 => [
                        'comment' => $this->getHelper()->__('Add or update data in product model table'),
                        'method'  => 'pimgento_api/job_product_model::updateData',
                    ],
                    6 => [
                        'comment' => $this->getHelper()->__('Drop temporary table'),
                        'method'  => 'pimgento_api/job_product_model::dropTable',
                    ],
                ],
            ]
        );
    }

    /**
     * Add Family Variant Import
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     */
    protected function familyVariant($task)
    {
        $task->addTask(
            'family_variant',
            [
                'label'   => $this->getHelper()->__('Family Variant'),
                'type'    => 'button',
                'comment' => $this->getHelper()->__('Family Variant from PIM.'),
                'options' => [],
                'steps'   => [
                    1 => [
                        'comment' => $this->getHelper()->__('Create temporary table'),
                        'method'  => 'pimgento_api/job_family_variant::createTable',
                    ],
                    2 => [
                        'comment' => $this->getHelper()->__('Fill data into temporary table'),
                        'method'  => 'pimgento_api/job_family_variant::insertData',
                    ],
                    3 => [
                        'comment' => $this->getHelper()->__('Update Axes column'),
                        'method'  => 'pimgento_api/job_family_variant::updateAxes',
                    ],
                    4 => [
                        'comment' => $this->getHelper()->__('Update Product Model'),
                        'method'  => 'pimgento_api/job_family_variant::updateProductModel',
                    ],
                    5 => [
                        'comment' => $this->getHelper()->__('Drop temporary table'),
                        'method'  => 'pimgento_api/job_family_variant::dropTable',
                    ],
                ],
            ]
        );
    }

    /**
     * Add Product Import
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     */
    protected function product(Task_Executor_Model_Task $task)
    {
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = $this->getHelper();

        $task->addTask(
            'product',
            [
                'label'   => $helper->__('Product'),
                'type'    => 'button',
                'comment' => $helper->__('Import product from PIM.'),
                'options' => [],
                'steps'   => [
                    1  => [
                        'comment' => $helper->__('Create temporary table'),
                        'method'  => 'pimgento_api/job_product::createTable',
                    ],
                    2  => [
                        'comment' => $helper->__('Fill data into temporary table'),
                        'method'  => 'pimgento_api/job_product::insertData',
                    ],
                    3  => [
                        'comment' => $helper->__('Update column name'),
                        'method'  => 'pimgento_api/job_product::updateColumns',
                    ],
                    4  => [
                        'comment' => $helper->__('Detect configurable products'),
                        'method'  => 'pimgento_api/job_product::createConfigurable',
                    ],
                    5  => [
                        'comment' => $helper->__('Match PIM code with Magento entity ID'),
                        'method'  => 'pimgento_api/job_product::matchEntity',
                    ],
                    6  => [
                        'comment' => $helper->__('Match family code with Magento ID'),
                        'method'  => 'pimgento_api/job_product::updateAttributeSetId',
                    ],
                    7  => [
                        'comment' => $helper->__('Update column values for options'),
                        'method'  => 'pimgento_api/job_product::updateOption',
                    ],
                    8  => [
                        'comment' => $helper->__('Create or update product entities'),
                        'method'  => 'pimgento_api/job_product::createEntities',
                    ],
                    9  => [
                        'comment' => $helper->__('Set values to attributes'),
                        'method'  => 'pimgento_api/job_product::setValues',
                    ],
                    10 => [
                        'comment' => $helper->__('Link configurable with children'),
                        'method'  => 'pimgento_api/job_product::linkConfigurable',
                    ],
                    11 => [
                        'comment' => $helper->__('Set products to websites'),
                        'method'  => 'pimgento_api/job_product::setWebsites',
                    ],
                    12 => [
                        'comment' => $helper->__('Set products to categories'),
                        'method'  => 'pimgento_api/job_product::setCategories',
                    ],
                    13 => [
                        'comment' => $helper->__('Init stock'),
                        'method'  => 'pimgento_api/job_product::initStock',
                    ],
                    14 => [
                        'comment' => $helper->__('Update related, up-sell and cross-sell products'),
                        'method'  => 'pimgento_api/job_product::setRelated',
                    ],
                    15 => [
                        'comment' => $helper->__('Import media files'),
                        'method'  => 'pimgento_api/job_product::importMedia',
                    ],
                    16 => [
                        'comment' => $helper->__('Import Asset Files'),
                        'method'  => 'pimgento_api/job_product::importAsset',
                    ],
                    17 => [
                        'comment' => $helper->__('Drop temporary table'),
                        'method'  => 'pimgento_api/job_product::dropTable',
                    ],
                    18 => [
                        'comment' => $helper->__('Reindex Data'),
                        'method'  => 'pimgento_api/job_product::reindex',
                    ],
                    19 => [
                        'comment' => $helper->__('Clear cache'),
                        'method'  => 'pimgento_api/job_product::cleanCache',
                    ],
                ],
            ]
        );
    }

    /**
     * Wrapper for catalog_product_delete_after event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function catalogProductDeleteAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getEvent()->getProduct();

        $this->deleteProductCode($product);
    }

    /**
     * Delete product code
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return void
     */
    protected function deleteProductCode(Mage_Catalog_Model_Product $product)
    {
        if (!$product->getId()) {
            return;
        }

        /** @var Pimgento_Api_Model_Entities $entity */
        $entity = Mage::getModel('pimgento_api/entities');
        /** @var Pimgento_Api_Model_Job_Product $import */
        $import = Mage::getSingleton('pimgento_api/job_product');

        $entity->deleteByEntityId($import->getCode(), $product->getId());
    }
}
