<?php

/**
 * Class Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Update
 *
 * @category  Class
 * @package   Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Update
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Update
{
    /**
     * @string LOWER_THAN
     */
    const LOWER_THAN = '<';
    /**
     * @string GREATER_THAN
     */
    const GREATER_THAN = '>';
    /**
     * @string BETWEEN
     */
    const BETWEEN = 'BETWEEN';
    /**
     * @string SINCE_LAST_N_DAYS
     */
    const SINCE_LAST_N_DAYS = 'SINCE LAST N DAYS';
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
            self::LOWER_THAN        => $helper->__('Before'),
            self::GREATER_THAN      => $helper->__('After'),
            self::BETWEEN           => $helper->__('Between'),
            self::SINCE_LAST_N_DAYS => $helper->__('Since last n days'),
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
         * @var int    $optionValue
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
