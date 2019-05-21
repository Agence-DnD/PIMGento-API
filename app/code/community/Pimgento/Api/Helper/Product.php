<?php

/**
 * Class Pimgento_Api_Helper_Product
 *
 * @category  Class
 * @package   Pimgento_Api_Helper_Product
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Helper_Product extends Pimgento_Api_Helper_Entities
{
    /**
     *
     *
     * @var string ASSOCIATIONS_KEY
     */
    const ASSOCIATIONS_KEY = 'associations';
    /**
     *
     *
     * @var string VALUES_KEY
     */
    const VALUES_KEY = 'values';
    /**
     * Reserved attribute prefix enabled flag
     *
     * @var bool $isPrefixEnabled
     */
    private $isPrefixEnabled;

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

            if ($this->isPrefixRequired($key) === true) {
                $key = Pimgento_Api_Helper_Attribute::RESERVED_ATTRIBUTE_CODE_PREFIX . $key;
            }

            if (!is_array($value)) {
                $columns->{$key} = $value;

                continue;
            }

            if (empty($value)) {
                $columns->{$key} = null;

                continue;
            }

            if ($key === self::ASSOCIATIONS_KEY) {
                $columns = $this->formatAssociations($columns, $value);

                continue;
            }

            if ($key === self::VALUES_KEY) {
                $columns = $this->formatValues($columns, $value);

                continue;
            }

            /**
             * @var int|string $locale
             * @var mixed      $localeValue
             */
            foreach ($value as $locale => $localeValue) {
                if (is_numeric($locale)) {
                    $columns->{$key} = implode(',', $value);

                    continue;
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
     * Format values field containing all the attribute values
     *
     * @param stdClass $columns
     * @param mixed[]  $values
     *
     * @return stdClass
     */
    private function formatValues(stdClass $columns, array $values)
    {
        /**
         * @var string  $attribute
         * @var mixed[] $variations
         */
        foreach ($values as $attribute => $variations) {
            if ($this->isPrefixRequired($attribute) === true) {
                $attribute = Pimgento_Api_Helper_Attribute::RESERVED_ATTRIBUTE_CODE_PREFIX . $attribute;
            }

            /** @var mixed[] $specifics */
            foreach ($variations as $specifics) {
                /** @var string $key */
                $key = $this->getKey($attribute, $specifics);

                // Attribute is a text, textarea, number, date, yes/no, simpleselect, file
                if (!is_array($specifics['data'])) {
                    $columns->{$key} = $specifics['data'];

                    continue;
                }
                // Attribute is a metric
                if (array_key_exists('amount', $specifics['data'])) {
                    $columns->{$key} = $specifics['data']['amount'];

                    continue;
                }
                // Attribute is a multiselect
                if (isset($specifics['data'][0]) && (!is_array($specifics['data'][0]) || !array_key_exists('amount', $specifics['data'][0]))) {
                    $columns->{$key} = implode(',', $specifics['data']);

                    continue;
                }
                // Attribute is a price
                /** @var mixed[] $price */
                foreach ($specifics['data'] as $price) {
                    if (!array_key_exists('currency', $price) || !array_key_exists('amount', $price)) {
                        continue;
                    }
                    /** @var string $priceKey */
                    $priceKey             = sprintf('%s-%s', $key, $price['currency']);
                    $columns->{$priceKey} = $price['amount'];
                }
            }
        }

        return $columns;
    }

    /**
     * Format associations field
     *
     * @param stdClass $associations
     * @param mixed[]  $values
     *
     * @return stdClass
     */
    private function formatAssociations(stdClass $associations, array $values)
    {
        /**
         * @var string  $type
         * @var mixed[] $entities
         */
        foreach ($values as $type => $entities) {
            /**
             * @var string   $entity
             * @var string[] $values
             */
            foreach ($entities as $entity => $details) {
                /** @var string $name */
                $name = sprintf('%s-%s', $type, $entity);
                if (empty($details)) {
                    $associations->{$name} = null;

                    continue;
                }
                $associations->{$name} = implode(',', $details);
            }
        }

        return $associations;
    }

    /**
     * Get attribute key to be inserted as a column
     *
     * @param string  $attribute
     * @param mixed[] $specifics
     *
     * @return string
     */
    private function getKey($attribute, array $specifics)
    {
        /** @var string $attribute */
        $attribute = strtolower($attribute);
        if (isset($specifics['locale']) && isset($specifics['scope'])) {
            return sprintf('%s-%s-%s', $attribute, $specifics['locale'], $specifics['scope']);
        }
        if (isset($specifics['locale'])) {
            return sprintf('%s-%s', $attribute, $specifics['locale']);
        }
        if (isset($specifics['scope'])) {
            return sprintf('%s-%s', $attribute, $specifics['scope']);
        }

        return $attribute;
    }

    /**
     * Check prefix requirement for given attribute code
     *
     * @param string $code
     *
     * @return bool
     */
    private function isPrefixRequired($code)
    {
        /** @var Pimgento_Api_Helper_Attribute $attributeHelper */
        $attributeHelper = Mage::helper('pimgento_api/attribute');
        if ($attributeHelper->isAttributeCodeReserved($code) && $this->getIsPrefixEnabled()) {
            return true;
        }

        return false;
    }

    /**
     * Check if reserved attribute prefix is enabled
     *
     * @return bool
     */
    private function getIsPrefixEnabled()
    {
        if (!isset($this->isPrefixEnabled)) {
            /** @var Pimgento_Api_Helper_Configuration $configurationHelper */
            $configurationHelper   = Mage::helper('pimgento_api/configuration');
            $this->isPrefixEnabled = $configurationHelper->isPrefixEnabled();
        }

        return $this->isPrefixEnabled;
    }
}
