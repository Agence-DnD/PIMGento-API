<?php

/**
 * Class Pimgento_Api_Helper_Attribute
 *
 * @category  Class
 * @package   Pimgento_Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://pimgento.com/
 */
class Pimgento_Api_Helper_Attribute extends Mage_Core_Helper_Abstract
{
    /**
     * Optional prefix for Magento reserved attribute code
     *
     * @var string RESERVED_ATTRIBUTE_CODE_PREFIX
     */
    const RESERVED_ATTRIBUTE_CODE_PREFIX = 'pim_';
    /**
     * Akeneo select attribute types
     *
     * @var string[] $akeneoSelectTypes
     */
    private $akeneoSelectTypes = [
        'pim_catalog_simpleselect',
        'pim_catalog_multiselect',
    ];
    /**
     * Magento reserved attributes as array
     *
     * @var string[] $reservedAttributes
     */
    private $reservedAttributes;

    /**
     * Get Akeneo Select Types
     *
     * @return string[]
     */
    public function getAkeneoSelectTypes()
    {
        return $this->akeneoSelectTypes;
    }

    /**
     * Get Magento reserved attributes codes as array
     *
     * @return string[]
     */
    private function getReservedAttributes()
    {
        if (empty($this->reservedAttributes)) {
            /** @var Mage_Catalog_Model_Product $productSingleton */
            $productSingleton         = Mage::getSingleton('catalog/product');
            $this->reservedAttributes = $productSingleton->getReservedAttributes();
        }

        return $this->reservedAttributes;
    }

    /**
     * Match Pim type with Magento attribute logic
     *
     * @param string $pimType
     *
     * @return mixed[]
     */
    public function getType($pimType = 'default')
    {
        /** @var string[] $types */
        $types = [
            'default'                      => 'text',
            'pim_catalog_identifier'       => 'text',
            'pim_catalog_text'             => 'text',
            'pim_catalog_metric'           => 'text',
            'pim_catalog_number'           => 'text',
            'pim_catalog_textarea'         => 'textarea',
            'pim_catalog_date'             => 'date',
            'pim_catalog_boolean'          => 'boolean',
            'pim_catalog_simpleselect'     => 'select',
            'pim_catalog_multiselect'      => 'multiselect',
            'pim_catalog_price_collection' => 'price',
            'pim_catalog_tax'              => 'tax',
        ];

        $types = array_merge($types, $this->getAdditionalTypes());

        /** @var string $type */
        $type = $types['default'];
        if (isset($types[$pimType])) {
            $type = $types[$pimType];
        }

        return $this->getConfiguration($type);
    }

    /**
     * Retrieve additional attribute types
     *
     * @return string[]
     */
    public function getAdditionalTypes()
    {
        /** @var array $additional */
        $additional = [];
        /** @var Pimgento_Api_Helper_Configuration $helper */
        $helper = Mage::helper('pimgento_api/configuration');
        /** @var mixed[] $types */
        $types = $helper->getAdditionalAttributeTypesMapping();
        if (empty($type) || !is_array($type)) {
            return $additional;
        }

        /** @var array $type */
        foreach ($types as $type) {
            $additional[$type['pim_type']] = $type['magento_type'];
        }

        return $additional;
    }

    /**
     * Retrieve configuration with input type
     *
     * @param string $inputType
     *
     * @return mixed[]
     */
    protected function getConfiguration($inputType = 'default')
    {
        /** @var mixed[] $types */
        $types = [
            'text'        => [
                'backend_type'   => 'varchar',
                'frontend_input' => 'text',
                'backend_model'  => null,
                'source_model'   => null,
                'frontend_model' => null,
            ],
            'textarea'    => [
                'backend_type'   => 'text',
                'frontend_input' => 'textarea',
                'backend_model'  => null,
                'source_model'   => null,
                'frontend_model' => null,
            ],
            'date'        => [
                'backend_type'   => 'datetime',
                'frontend_input' => 'date',
                'backend_model'  => 'eav/entity_attribute_backend_datetime',
                'source_model'   => null,
                'frontend_model' => null,
            ],
            'boolean'     => [
                'backend_type'   => 'int',
                'frontend_input' => 'boolean',
                'backend_model'  => null,
                'source_model'   => 'eav/entity_attribute_source_boolean',
                'frontend_model' => null,
            ],
            'multiselect' => [
                'backend_type'   => 'varchar',
                'frontend_input' => 'multiselect',
                'backend_model'  => 'eav/entity_attribute_backend_array',
                'source_model'   => null,
                'frontend_model' => null,
            ],
            'select'      => [
                'backend_type'   => 'int',
                'frontend_input' => 'select',
                'backend_model'  => null,
                'source_model'   => 'eav/entity_attribute_source_table',
                'frontend_model' => null,
            ],
            'price'       => [
                'backend_type'   => 'decimal',
                'frontend_input' => 'price',
                'backend_model'  => 'catalog/product_attribute_backend_price',
                'source_model'   => null,
                'frontend_model' => null,
            ],
            'media_image' => [
                'backend_type'   => 'varchar',
                'frontend_input' => 'media_image',
                'backend_model'  => 'catalog/product_attribute_backend_media',
                'source_model'   => null,
                'frontend_model' => null,
            ],
            'default'     => [
                'backend_type'   => 'varchar',
                'frontend_input' => 'text',
                'backend_model'  => null,
                'source_model'   => null,
                'frontend_model' => null,
            ],
        ];

        /** @var mixed[] $type */
        $type = $types['default'];
        if (isset($types[$inputType])) {
            $type = $types[$inputType];
        }

        return $type;
    }

    /**
     * Get the specific columns that depends on the attribute type
     *
     * @return mixed[]
     */
    public function getSpecificColumns()
    {
        /** @var mixed[] $columns */
        $columns = [
            'backend_type'   => [
                'type'      => 'VARCHAR(255) NULL',
                'only_init' => true,
            ],
            'frontend_input' => [
                'type'      => 'VARCHAR(255) NULL',
                'only_init' => true,
            ],
            'backend_model'  => [
                'type'      => 'VARCHAR(255) NULL',
                'only_init' => true,
            ],
            'source_model'   => [
                'type'      => 'VARCHAR(255) NULL',
                'only_init' => true,
            ],
            'frontend_model' => [
                'type'      => 'VARCHAR(255) NULL',
                'only_init' => false,
            ],
        ];

        /** @var Varien_Object $response */
        $response = new Varien_Object();
        $response->setColumns($columns);

        Mage::dispatchEvent(
            'pimgento_attribute_get_specific_columns_add_after',
            ['response' => $response]
        );

        /** @var mixed[] $columns */
        $columns = $response->getColumns();

        return $columns;
    }

    /**
     * Check if attribute code is reserved by Magento
     *
     * @param string $attributeCode
     *
     * @return bool
     */
    public function isAttributeCodeReserved($attributeCode)
    {
        /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attributeModel */
        $attributeModel = Mage::getResourceModel('eav/entity_attribute');
        /** @var int $id */
        $id = $attributeModel->getIdByCode(Mage_Catalog_Model_Product::ENTITY, $attributeCode);

        if (in_array($attributeCode, $this->getReservedAttributes()) && empty($id)) {
            return true;
        }

        return false;
    }
}
