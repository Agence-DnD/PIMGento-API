<?php

/**
 * Class Pimgento_Api_Helper_Locales
 *
 * @category  Class
 * @package   Pimgento_Api_Helper_Locales
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Helper_Locales extends Pimgento_Api_Helper_Data
{
    /**
     * Get active Akeneo locales
     *
     * @return string[]
     * @throws Pimgento_Api_Exception
     */
    public function getAkeneoLocales()
    {
        /** @var Pimgento_Api_Helper_Client $helperClient */
        $helperClient = Mage::helper('pimgento_api/client');
        /** @var Akeneo\Pim\ApiClient\AkeneoPimClientInterface|Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface $apiClient */
        $apiClient = $helperClient->getApiClient();
        /** @var \Akeneo\Pim\ApiClient\Api\LocaleApiInterface $localeApi */
        $localeApi = $apiClient->getLocaleApi();
        /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface $locales */
        $locales = $localeApi->all(
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

        /** @var string[] $akeneoLocales */
        $akeneoLocales = [];
        /** @var mixed[] $locale */
        foreach ($locales as $locale) {
            if (empty($locale['code'])) {
                continue;
            }
            $akeneoLocales[] = $locale['code'];
        }

        return $akeneoLocales;
    }
}
