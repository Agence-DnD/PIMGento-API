<?php

/**
 * Class Pimgento_Api_Helper_Entities
 *
 * @category  Class
 * @package   Pimgento_Api_Helper_Entities
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Helper_Entities extends Mage_Core_Helper_Abstract
{
    /**
     * Entities excluded columns
     *
     * @var string[] EXCLUDED_COLUMNS
     */
    const EXCLUDED_COLUMNS = [
        '_links',
        'metadata',
    ];
    /**
     * Catalog product entity type id
     *
     * @var string $productEntityTypeId
     */
    protected $productEntityTypeId;
    /**
     * Category entity type id
     *
     * @var int $categoryEntityTypeId
     */
    protected $categoryEntityTypeId;
    /**
     * Category default attribute set id
     *
     * @var int $categoryDefaultAttributeSetId
     */
    protected $categoryDefaultAttributeSetId;
    /**
     * Product default attribute set id
     *
     * @var int $productDefaultAttributeSetId
     */
    protected $productDefaultAttributeSetId;

    /**
     * Retrieve product entity type id
     *
     * @return string
     */
    public function getProductEntityTypeId()
    {
        if (empty($this->productEntityTypeId)) {
            $this->productEntityTypeId = Mage::getSingleton('eav/config')->getEntityType(
                Mage_Catalog_Model_Product::ENTITY
            )->getId();
        }

        return $this->productEntityTypeId;
    }

    /**
     * Retrieve product default attribute set id
     *
     * @return int
     */
    public function getProductDefaultAttributeSetId()
    {
        if (empty($this->productDefaultAttributeSetId)) {
            /** @var Mage_Eav_Model_Config $eav */
            $eav                                = Mage::getSingleton('eav/config');
            $this->productDefaultAttributeSetId = $eav->getEntityType(Mage_Catalog_Model_Product::ENTITY)->getDefaultAttributeSetId();
        }

        return $this->productDefaultAttributeSetId;
    }

    /**
     * Retrieve category entity type id
     *
     * @return int
     */
    public function getCategoryEntityTypeId()
    {
        if (empty($this->categoryEntityTypeId)) {
            /** @var Mage_Catalog_Model_Category $category */
            $category                   = Mage::getSingleton('catalog/category');
            $this->categoryEntityTypeId = $category->getResource()->getTypeId();
        }

        return $this->categoryEntityTypeId;
    }

    /**
     * Retrieve category default attribute set id
     *
     * @return int
     */
    public function getCategoryDefaultAttributeSetId()
    {
        if (empty($this->categoryDefaultAttributeSetId)) {
            /** @var Mage_Eav_Model_Config $eav */
            $eav                                 = Mage::getSingleton('eav/config');
            $this->categoryDefaultAttributeSetId = $eav->getEntityType(Mage_Catalog_Model_Category::ENTITY)->getDefaultAttributeSetId();
        }

        return $this->categoryDefaultAttributeSetId;
    }

    /**
     * Format column name
     *
     * @param string $column
     *
     * @return string
     */
    public function formatColumn($column)
    {
        /** @var string $formattedColumn */
        $formattedColumn = trim($column);
        $formattedColumn = preg_replace('/\s+/', ' ', $formattedColumn);
        $formattedColumn = str_replace(PHP_EOL, '', $formattedColumn);
        $formattedColumn = trim($formattedColumn, '""');

        return $formattedColumn;
    }

    /**
     * Retrieve column names only from API result
     *
     * @param mixed[] $result
     *
     * @return string[]
     */
    public function getColumnNamesFromResult(array $result)
    {
        /** @var string[] $columns */
        $columns = $this->getColumnsFromResult($result);
        /** @var string[] $columnNames */
        $columnNames = array_keys($columns);

        return $columnNames;
    }

    /**
     * Retrieve table column names from Api result
     *
     * @param mixed[] $result
     *
     * @return string[]
     */
    public function getColumnsFromResult(array $result)
    {
        /** @var stdClass $columns */
        $columns = new stdClass();
        /**
         * @var string $key
         * @var mixed  $value
         */
        foreach ($result as $key => $value) {
            if (in_array($key, static::EXCLUDED_COLUMNS)) {
                continue;
            }

            if (!is_array($value)) {
                $columns->{$key} = $value;

                continue;
            }

            if (empty($value)) {
                $columns->{$key} = null;

                continue;
            }

            /**
             * @var int|string $locale
             * @var mixed      $localeValue
             */
            foreach ($value as $locale => $localeValue) {
                if (is_numeric($locale)) {
                    $columns->{$key} = implode(',', $value);

                    break;
                }

                /** @var mixed $data */
                $data = $localeValue;
                if (is_array($localeValue)) {
                    $data = implode(',', $localeValue);
                }
                /** @var string $columnKey */
                $columnKey             = sprintf('%s-%s', $key, $locale);
                $columns->{$columnKey} = $data;
            }
        }

        return (array)$columns;
    }

    /**
     * Set key to lower case
     * to avoid problems with values import
     *
     * @param string $key
     *
     * @return string
     */
    public function keyToLowerCase($key)
    {
        /** @var string[] $keyParts */
        $keyParts    = explode('-', $key, 2);
        $keyParts[0] = strtolower($keyParts[0]);
        /** @var string $newKey */
        if (count($keyParts) > 1) {
            $newKey = $keyParts[0].'-'.$keyParts[1];
        } else {
            $newKey = $keyParts[0];
        }

        return $newKey;
    }
}
