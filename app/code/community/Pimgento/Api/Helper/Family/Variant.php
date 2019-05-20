<?php

/**
 * Class Pimgento_Api_Helper_Family_Variant
 *
 * @category  Class
 * @package   Pimgento_Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Helper_Family_Variant extends Pimgento_Api_Helper_Entities
{
    /**
     * Family variant maximum authorized axes
     *
     * @var int MAX_AXES_NUMBER
     */
    const MAX_AXES_NUMBER = 5;

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
            $key = $this->keyToLowerCase($key);
            if (in_array($key, static::EXCLUDED_COLUMNS)) {
                continue;
            }

            if (empty($value)) {
                $columns->{$key} = null;

                continue;
            }

            if (!is_array($value)) {
                $columns->{$key} = $value;

                continue;
            }

            /**
             * @var string|int     $index
             * @var string|mixed[] $data
             */
            foreach ($value as $index => $data) {
                if ($key == 'variant_attribute_sets') {
                    $columns->{sprintf('variant-axes_%s', $data['level'])}       = implode(',', $data['axes']);
                    $columns->{sprintf('variant-attributes_%s', $data['level'])} = implode(',', $data['attributes']);

                    continue;
                }

                if (is_numeric($index)) {
                    $columns->{$key} = implode(',', $value);

                    continue;
                }

                if (is_array($data)) {
                    $data = implode(',', $data);
                }
                /** @var string $columnKey */
                $columnKey             = sprintf('%s-%s', $key, $index);
                $columns->{$columnKey} = $data;
            }
        }

        return (array)$columns;
    }
}
