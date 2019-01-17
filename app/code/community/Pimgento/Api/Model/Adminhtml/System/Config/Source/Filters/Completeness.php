<?php

/**
 * Class Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Completeness
 *
 * @category  Class
 * @package   Pimgento_Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Completeness
{
    /**
     * @string LOWER_THAN
     */
    const LOWER_THAN = '<';
    /**
     * @string LOWER_OR_EQUALS_THAN
     */
    const LOWER_OR_EQUALS_THAN = '<=';
    /**
     * @string GREATER_THAN
     */
    const GREATER_THAN = '>';
    /**
     * @string GREATER_OR_EQUALS_THAN
     */
    const GREATER_OR_EQUALS_THAN = '>=';
    /**
     * @string EQUALS
     */
    const EQUALS = '=';
    /**
     * @string DIFFER
     */
    const DIFFER = '!=';
    /**
     * @string GREATER_THAN_ON_ALL_LOCALES
     */
    const GREATER_THAN_ON_ALL_LOCALES = 'GREATER THAN ON ALL LOCALES';
    /**
     * @string GREATER_OR_EQUALS_THAN_ON_ALL_LOCALES
     */
    const GREATER_OR_EQUALS_THAN_ON_ALL_LOCALES = 'GREATER OR EQUALS THAN ON ALL LOCALES';
    /**
     * @string LOWER_THAN_ON_ALL_LOCALES
     */
    const LOWER_THAN_ON_ALL_LOCALES = 'LOWER THAN ON ALL LOCALES';
    /**
     * @string LOWER_OR_EQUALS_THAN_ON_ALL_LOCALES
     */
    const LOWER_OR_EQUALS_THAN_ON_ALL_LOCALES = 'LOWER OR EQUALS THAN ON ALL LOCALES';

    /**
     * List of options
     *
     * @var array $options
     */
    protected $options = [];

    /**
     * Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Completeness constructor
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize options
     *
     * @return void
     */
    public function init()
    {
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = Mage::helper('pimgento_api');

        $this->options = [
            self::LOWER_THAN                            => $helper->__('Lower than'),
            self::LOWER_OR_EQUALS_THAN                  => $helper->__('Lower or equals than'),
            self::GREATER_THAN                          => $helper->__('Greater than'),
            self::GREATER_OR_EQUALS_THAN                => $helper->__('Greater or equals than'),
            self::EQUALS                                => $helper->__('Equals'),
            self::DIFFER                                => $helper->__('Differ'),
            self::GREATER_THAN_ON_ALL_LOCALES           => $helper->__('Greater than on all locales'),
            self::GREATER_OR_EQUALS_THAN_ON_ALL_LOCALES => $helper->__('Greater or equals than on all locales'),
            self::LOWER_THAN_ON_ALL_LOCALES             => $helper->__('Lower than on all locales'),
            self::LOWER_OR_EQUALS_THAN_ON_ALL_LOCALES   => $helper->__('Lower or equals than on all locales')
        ];
    }

    /**
     * Retrieve option list
     *
     * @return array
     */
    public function toOptions()
    {
        return $this->options;
    }

    /**
     * Retrieve options value and label in an array
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var array $optionArray */
        $optionArray = [];
        /**
         * @var int $optionValue
         * @var string $optionLabel
         */
        foreach ($this->options as $optionValue => $optionLabel) {
            $optionArray[] = [
                'value' => $optionValue,
                'label' => $optionLabel,
            ];
        }

        return $optionArray;
    }
}
