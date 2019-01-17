<?php

/**
 * Class Pimgento_Api_Helper_Store
 *
 * @category  Class
 * @package   Pimgento_Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Helper_Store extends Mage_Core_Helper_Data
{
    /**
     * Retrieve all stores information
     *
     * @param string|string[] $arrayKey
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getStores($arrayKey = 'store_id')
    {
        if (!is_array($arrayKey)) {
            $arrayKey = [$arrayKey];
        }

        /** @var mixed[] $data */
        $data = [];
        /** @var Pimgento_Api_Helper_Configuration $configHelper */
        $configHelper = $this->getConfigurationHelper();
        /** @var mixed[] $mapping */
        $mapping = $configHelper->getWebsiteMapping();
        /** @var string[] $match */
        foreach ($mapping as $match) {
            if (empty($match['channel']) || empty($match['website'])) {
                continue;
            }
            /** @var string $channel */
            $channel = $match['channel'];
            /** @var string $websiteCode */
            $websiteCode = $match['website'];
            /** @var Mage_Core_Model_Website $website */
            $website = Mage::app()->getWebsite($websiteCode);
            /** @var int $websiteId */
            $websiteId = $website->getId();
            if (!isset($websiteId)) {
                continue;
            }

            /** @var Mage_Core_Model_Store[] $store */
            $stores = $website->getStores();
            /** @var Mage_Core_Model_Store $store */
            foreach ($stores as $store) {
                /** @var mixed[] $combine */
                $combine = [];
                /** @var string $key */
                foreach ($arrayKey as $key) {
                    switch ($key) {
                        case 'store_id':
                            $combine[] = $store->getId();
                            break;
                        case 'store_code':
                            $combine[] = $store->getCode();
                            break;
                        case 'website_id':
                            $combine[] = $websiteId;
                            break;
                        case 'website_code':
                            $combine[] = $websiteCode;
                            break;
                        case 'channel_code':
                            $combine[] = $channel;
                            break;
                        case 'lang':
                            $combine[] = Mage::getStoreConfig(
                                Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE,
                                $store->getId()
                            );
                            break;
                        case 'currency':
                            $combine[] = $store->getDefaultCurrencyCode();
                            break;
                        default:
                            $combine[] = $store->getId();
                            break;
                    }
                }

                /** @var string $key */
                $key = implode('-', $combine);

                $data[$key][] = [
                    'store_id'     => $store->getId(),
                    'store_code'   => $store->getCode(),
                    'website_id'   => $websiteId,
                    'website_code' => $websiteCode,
                    'channel_code' => $channel,
                    'lang'         => Mage::getStoreConfig(
                        Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE,
                        $store->getId()
                    ),
                    'currency'     => $store->getDefaultCurrencyCode(),
                ];
            }
        }

        return $data;
    }

    /**
     * Retrieve all store combination
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getAllStores()
    {
        /** @var mixed[] $stores */
        $stores = array_merge(
            $this->getStores(['lang']), // en_US
            $this->getStores(['lang', 'channel_code']), // en_US-channel
            $this->getStores(['channel_code']), // channel
            $this->getStores(['currency']), // USD
            $this->getStores(['channel_code', 'currency']), // channel-USD
            $this->getStores(['lang', 'channel_code', 'currency']) // en_US-channel-USD
        );

        return $stores;
    }

    /**
     * Retrieve all stores from website/channel mapping
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getMappedWebsitesStores()
    {
        /** @var string[] $mapping */
        $mapping = $this->getConfigurationHelper()->getWebsiteMapping();
        if (empty($mapping)) {
            return [];
        }

        /** @var mixed[] $stores */
        $stores = $this->getStores('website_code');
        /** @var mixed[] $mappedWebsites */
        $mappedWebsites = array_column($mapping, 'website');
        $mappedWebsites = array_flip($mappedWebsites);
        $mappedWebsites = array_intersect_key($stores, $mappedWebsites);

        return $mappedWebsites;
    }

    /**
     * Retrieve needed store ids from website/channel mapping
     *
     * @return string[]
     * @throws Exception
     */
    public function getMappedWebsitesStoreIds()
    {
        /** @var string[] $websites */
        $websites = $this->getMappedWebsitesStores();
        /** @var string[] $storeIds */
        $storeIds = [];
        /** @var mixed[] $website */
        foreach ($websites as $website) {
            /** @var string[] $websiteStoreIds */
            $websiteStoreIds = array_column($website, 'store_id');
            $storeIds        = array_merge($storeIds, array_diff($websiteStoreIds, $storeIds));
        }

        return $storeIds;
    }

    /**
     * Retrieve needed store languages from website/channel mapping
     *
     * @return string[]
     * @throws Exception
     */
    public function getMappedWebsitesStoreLangs()
    {
        /** @var string[] $websites */
        $websites = $this->getMappedWebsitesStores();
        /** @var string[] $langs */
        $langs = [];
        /** @var mixed[] $website */
        foreach ($websites as $website) {
            /** @var string[] $websiteStoreIds */
            $websiteStoreIds = array_column($website, 'lang');
            $langs           = array_merge($langs, array_diff($websiteStoreIds, $langs));
        }

        return $langs;
    }

    /**
     * Retrieve admin store lang setting
     * Default: return Mage_Core_Model_Locale::DEFAULT_LOCALE
     *
     * @return string
     */
    public function getAdminLang()
    {
        /** @var string $adminLang */
        $adminLang = Mage_Core_Model_Locale::DEFAULT_LOCALE;

        if (Mage::getStoreConfigFlag(
            Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE,
            Mage_Core_Model_App::ADMIN_STORE_ID
        )) {
            $adminLang = Mage::getStoreConfig(
                Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE,
                Mage_Core_Model_App::ADMIN_STORE_ID
            );
        }

        return $adminLang;
    }

    /**
     * Get configuration helper instance
     *
     * @return Pimgento_Api_Helper_Configuration
     */
    protected function getConfigurationHelper()
    {
        return Mage::helper('pimgento_api/configuration');
    }
}
