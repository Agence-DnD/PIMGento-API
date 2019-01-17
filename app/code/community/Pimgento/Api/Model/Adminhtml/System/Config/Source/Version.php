<?php

/**
 * Class Pimgento_Api_Model_Adminhtml_System_Config_Source_Version
 *
 * @category  Class
 * @package   Pimgento_Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Adminhtml_System_Config_Source_Version
{
    /**
     * Community version value
     *
     * @var string COMMUNITY_VALUE
     */
    const COMMUNITY_VALUE = '1';
    /**
     * Community version label
     *
     * @var string COMMUNITY_LABEL
     */
    const COMMUNITY_LABEL = 'Community';
    /**
     * Enterprise version value
     *
     * @var string ENTERPRISE_VALUE
     */
    const ENTERPRISE_VALUE = '2';
    /**
     * Enterprise version label
     *
     * @var string ENTERPRISE_LABEL
     */
    const ENTERPRISE_LABEL = 'Enterprise';

    /**
     * List of options
     *
     * @var string[] $options
     */
    protected $options = [];

    /**
     * Pimgento_Api_Model_Adminhtml_System_Config_Source_Version constructor
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
            self::COMMUNITY_VALUE  => Mage::helper('pimgento_api')->__(self::COMMUNITY_LABEL),
            self::ENTERPRISE_VALUE => Mage::helper('pimgento_api')->__(self::ENTERPRISE_LABEL),
        ];
    }

    /**
     * Retrieve option list
     *
     * @return string[]
     */
    public function toOptions()
    {
        return $this->options;
    }

    /**
     * Retrieve options value and label in an array
     *
     * @return mixed[]
     */
    public function toOptionArray()
    {
        /** @var mixed[] $optionArray */
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
