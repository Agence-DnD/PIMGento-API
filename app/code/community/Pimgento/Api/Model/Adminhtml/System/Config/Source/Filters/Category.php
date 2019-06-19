<?php

/**
 * Class Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Category
 *
 * @category  Class
 * @package   Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Category
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Category
{
    /**
     * List of options
     *
     * @var array $options
     */
    protected $options = [];

    /**
     * Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Family constructor
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
        /** @var Pimgento_Api_Helper_Client $helperClient */
        $helperClient = Mage::helper('pimgento_api/client');

        try {
            /** @var Akeneo\Pim\ApiClient\AkeneoPimClientInterface|Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface $client */
            $client = $helperClient->getApiClient();

            $this->options[''] = Mage::helper('pimgento_api')->__('None');

            if (empty($client)) {
                return;
            }

            /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface $categories */
            $categories = $client->getCategoryApi()->all();
            /** @var mixed[] $category */
            foreach ($categories as $category) {
                if (!isset($category['code']) || isset($category['parent'])) {
                    continue;
                }
                $this->options[$category['code']] = $category['code'];
            }
        } catch (Exception $exception) {
            Mage::logException($exception);
        }
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
