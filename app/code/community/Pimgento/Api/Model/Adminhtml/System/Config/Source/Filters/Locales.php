<?php

/**
 * Class Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Locales
 *
 * @category  Class
 * @package   Pimgento_Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Locales
{
    /**
     * List of options
     *
     * @var array $options
     */
    protected $options = [];

    /**
     * Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Locales constructor
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

            if (empty($client)) {
                return;
            }

            /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface $locales */
            $locales = $client->getLocaleApi()->all(
                10,
                [
                    'search' => [
                        'enabled' => [
                            [
                                'operator' => '=',
                                'value'    => true,
                            ],
                        ],
                    ],
                ]
            );
            /** @var mixed[] $locale */
            foreach ($locales as $locale) {
                if (!isset($locale['code'])) {
                    continue;
                }
                $this->options[$locale['code']] = $locale['code'];
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
