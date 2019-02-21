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
        /** @var string[] $websiteDefaultStores */
        $websiteDefaultStores = $this->getWebsiteDefaultStores(true);
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

            /** @var string $currency */
            $currency = $website->getBaseCurrencyCode();
            /** @var string[] $siblings */
            $siblings = $website->getStoreIds();
            /** @var Mage_Core_Model_Store[] $store */
            $stores = $website->getStores();
            /** @var Mage_Core_Model_Store $store */
            foreach ($stores as $store) {
                /** @var int $storeId */
                $storeId = $store->getId();
                /** @var string $storeCode */
                $storeCode = $store->getCode();
                /** @var string $storeLang */
                $storeLang = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $storeId);
                /** @var bool $isDefault */
                $isDefault = false;
                if (in_array($storeId, $websiteDefaultStores)) {
                    $isDefault = true;
                }

                /** @var mixed[] $combine */
                $combine = [];
                /** @var string $key */
                foreach ($arrayKey as $key) {
                    switch ($key) {
                        case 'store_id':
                            $combine[] = $storeId;
                            break;
                        case 'store_code':
                            $combine[] = $storeCode;
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
                            $combine[] = $storeLang;
                            break;
                        case 'currency':
                            $combine[] = $currency;
                            break;
                        default:
                            $combine[] = $storeId;
                            break;
                    }
                }

                /** @var string $key */
                $key = implode('-', $combine);

                $data[$key][] = [
                    'store_id'           => $storeId,
                    'store_code'         => $storeCode,
                    'is_website_default' => $isDefault,
                    'siblings'           => $siblings,
                    'website_id'         => $websiteId,
                    'website_code'       => $websiteCode,
                    'channel_code'       => $channel,
                    'lang'               => $storeLang,
                    'currency'           => $currency,
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
     * Retrieve needed store ids from website/channel mapping
     *
     * @return string[]
     * @throws Exception
     */
    public function getMappedWebsitesStoreIds()
    {
        /** @var mixed[] $websites */
        $websites = $this->getStores('website_code');
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
        /** @var mixed[] $websites */
        $websites = $this->getStores('website_code');
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
     * Get websites default stores
     *
     * @param bool $withAdmin
     *
     * @return string[]
     */
    public function getWebsiteDefaultStores($withAdmin = false)
    {
        /** @var Mage_Core_Model_Resource_Website $websiteResource */
        $websiteResource = Mage::getResourceModel('core/website');
        /** @var Varien_Db_Select $select */
        $select = $websiteResource->getDefaultStoresSelect($withAdmin);
        /** @var string[] $websiteDefaultStores */
        $websiteDefaultStores = $websiteResource->getReadConnection()->fetchPairs($select);

        return $websiteDefaultStores;
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
