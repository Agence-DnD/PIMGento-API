<?php

/**
 * Class Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Status
 *
 * @category  Class
 * @package   Pimgento_Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Status
{
    /**
     * @var string STATUS_NO_CONDITION
     */
    const STATUS_NO_CONDITION = 'no_condition';
    /**
     * @var bool STATUS_ENABLED
     */
    const STATUS_ENABLED = 1;
    /**
     * @var bool STATUS_ENABLED
     */
    const STATUS_DISABLED = 2;

    /**
     * List of options
     *
     * @var array $options
     */
    protected $options = [];

    /**
     * Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Status constructor
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
            self::STATUS_NO_CONDITION => $helper->__('No condition'),
            self::STATUS_ENABLED      => $helper->__('Enabled'),
            self::STATUS_DISABLED     => $helper->__('Disabled'),
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
