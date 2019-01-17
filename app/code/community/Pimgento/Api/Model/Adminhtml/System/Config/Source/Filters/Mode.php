<?php

/**
 * Class Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Mode
 *
 * @category  Class
 * @package   Pimgento_Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Mode
{
    /**
     * Standard value
     *
     * @var string STANDARD_VALUE
     */
    const STANDARD_VALUE = 'standard';
    /**
     * Standard label
     *
     * @var string STANDARD_LABEL
     */
    const STANDARD_LABEL = 'Standard';
    /**
     * Advanced value
     *
     * @var string ADVANCED_VALUE
     */
    const ADVANCED_VALUE = 'advanced';
    /**
     * Advanced label
     *
     * @var string ADVANCED_LABEL
     */
    const ADVANCED_LABEL = 'Advanced';

    /**
     * List of options
     *
     * @var array $options
     */
    protected $options = [];

    /**
     * Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Mode constructor
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
        $this->options = [
            self::STANDARD_VALUE => Mage::helper('pimgento_api')->__(self::STANDARD_LABEL),
            self::ADVANCED_VALUE => Mage::helper('pimgento_api')->__(self::ADVANCED_LABEL),
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
