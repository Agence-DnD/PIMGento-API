<?php

/**
 * Class Pimgento_Api_Helper_Configuration
 *
 * @category  Class
 * @package   Pimgento_Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Helper_Configuration extends Mage_Core_Helper_Abstract
{
    /**
     * Module config section
     *
     * @var string $configSection
     */
    private $configSection = 'pimgento_api';
    /**
     * Akeneo Credentials config path
     *
     * @var string $credentialsConfigGroup
     */
    private $credentialsConfigGroup = 'credentials';
    /**
     * Akeneo General config path
     *
     * @var string $generalConfigGroup
     */
    private $generalConfigGroup = 'general';
    /**
     * Product config path
     *
     * @var string $productConfigGroup
     */
    private $productConfigGroup = 'product';
    /**
     * Product config path
     *
     * @var string $productFilterConfigGroup
     */
    private $productFilterConfigGroup = 'products_filters';
    /**
     * Logs config group
     *
     * @var string $logsConfigGroup
     */
    private $logsConfigGroup = 'logs';
    /**
     * Akeneo Url config field
     *
     * @var string $baseUrlConfigField
     */
    private $baseUrlConfigField = 'base_url';
    /**
     * Akeneo Version config field
     *
     * @var string $versionConfigField
     */
    private $versionConfigField = 'akeneo_version';
    /**
     * Pimgento Api Client ID config field
     *
     * @var string $clientIdConfigField
     */
    private $clientIdConfigField = 'client_id';
    /**
     * Akeneo Secret config field
     *
     * @var string $secretConfigField
     */
    private $secretConfigField = 'secret';
    /**
     * Akeneo User config field
     *
     * @var string $userConfigField
     */
    private $userConfigField = 'user';
    /**
     * Akeneo Pass config field
     *
     * @var string $passConfigField
     */
    private $passConfigField = 'pass';
    /**
     * Module log enabled config field
     *
     * @var string $logEnabledConfigField
     */
    private $logEnabledConfigField = 'log';
    /**
     * Import reindexation enabling select field
     *
     * @var string $reindexEnabledConfigField
     */
    private $reindexEnabledConfigField = 'reindex';
    /**
     * Import cache clean enabling select field
     *
     * @var string $cacheClearEnabledConfigField
     */
    private $cacheClearEnabledConfigField = 'clear_cache';
    /**
     * Import cache list
     *
     * @var string $cacheListConfigField
     */
    private $cacheListConfigField = 'cache_list';
    /**
     * Module log file config field
     *
     * @var string $paginationSizeConfigField
     */
    private $paginationSizeConfigField = 'pagination_size';
    /**
     * Admin website channel config field
     *
     * @var string $adminWebsiteChannelConfigField
     */
    private $adminWebsiteChannelConfigField = 'admin_channel';
    /**
     * Akeneo channel website mapping config field
     *
     * @var string $websiteMappingConfigField
     */
    private $websiteMappingConfigField = 'website_mapping';
    /**
     * Akeneo API default page size
     *
     * @var int $defaultPaginationSize
     */
    private $defaultPaginationSize = 10;
    /**
     * Product attribute transformer
     *
     * @var string $productAttributeMappingConfigField
     */
    private $productAttributeMappingConfigField = 'attribute_mapping';
    /**
     * Product default tax
     *
     * @var string $productTaxIdConfigField
     */
    private $productTaxIdConfigField = 'tax_id';
    /**
     * Attribute config path
     *
     * @var string $attributeConfigGroup
     */
    private $attributeConfigGroup = 'attribute';
    /**
     * Additional attribute types config field
     *
     * @var string $additionalAttributeTypes
     */
    private $additionalAttributeTypes = 'additional_types';
    /**
     * Reserved attributes prefix activation config field
     *
     * @var string $prefixReservedConfigField
     */
    private $prefixReservedConfigField = 'prefix_reserved';
    /**
     * Configurable Attributes
     *
     * @var string $productConfigurableAttributesConfigField
     */
    private $productConfigurableAttributesConfigField = 'configurable_attributes';
    /**
     * Image importation enabled
     *
     * @var string $productImageEnabledConfigField
     */
    private $productImageEnabledConfigField = 'image_enabled';
    /**
     * Image gallery attributes
     *
     * @var string $productImageGalleryAttributesConfigField
     */
    private $productImageGalleryAttributesConfigField = 'image_gallery_attributes';
    /**
     * Image images attributes
     *
     * @var string $productImageImagesAttributesConfigField
     */
    private $productImageImagesAttributesConfigField = 'image_images_attributes';
    /**
     * Asset importation enabled
     *
     * @var string $productAssetEnabledConfigField
     */
    private $productAssetEnabledConfigField = 'asset_enabled';
    /**
     * Asset gallery attributes
     *
     * @var string $productAssetGalleryAttributesConfigField
     */
    private $productAssetGalleryAttributesConfigField = 'asset_gallery_attributes';
    /**
     * Product Filter config field
     *
     * @var string $productsFiltersMode
     */
    private $productsFiltersMode = 'mode';
    /**
     * Product Filter Completness Enabled config field
     *
     * @var string $productsFiltersCompletenessEnabled
     */
    private $productsFiltersCompletenessEnabled = 'completeness_enabled';
    /**
     * Product Filter Completness Type config field
     *
     * @var string $productsFiltersCompletenessType
     */
    private $productsFiltersCompletenessType = 'completeness_type';
    /**
     * Product Filter Completness Value config field
     *
     * @var string $productsFiltersCompletenessValue
     */
    private $productsFiltersCompletenessValue = 'completeness_value';
    /**
     * Product Filter Completness Scope config field
     *
     * @var string $productsFiltersCompletenessScope
     */
    private $productsFiltersCompletenessScope = 'completeness_scope';
    /**
     * Product Filter Completness Locales config field
     *
     * @var string $productsFiltersCompletenessLocales
     */
    private $productsFiltersCompletenessLocales = 'completeness_locales';
    /**
     * Product Filter Status config field
     *
     * @var string $productsFiltersStatus
     */
    private $productsFiltersStatus = 'status';
    /**
     * Product Filter Families config field
     *
     * @var string $productsFiltersFamilies
     */
    private $productsFiltersFamilies = 'families';
    /**
     * Product Filter Updated config field
     *
     * @var string $productsFiltersUpdated
     */
    private $productsFiltersUpdated = 'updated';
    /**
     * Product Filter Advanced Filter config field
     *
     * @var string $productsFiltersAdvancedFilter
     */
    private $productsFiltersAdvancedFilter = 'advanced_filter';

    /**
     * Retrieve config value
     *
     * @param string $configPath
     * @param bool   $encrypted
     *
     * @return string
     */
    protected function getConfigValue($configPath, $encrypted = false)
    {
        if (!Mage::getStoreConfigFlag($configPath)) {
            return '';
        }
        /** @var Mage_Core_Helper_Data $helper */
        $helper = Mage::helper('core');
        /** @var string $configValue */
        $configValue = (string)Mage::getStoreConfig($configPath);
        if (!$encrypted) {
            return $configValue;
        }
        /** @var string $configValueDecrypted */
        $configValueDecrypted = $helper->decrypt($configValue);

        return $configValueDecrypted;
    }

    /**
     * Retrieve Credentials config value
     *
     * @param string $configFieldName
     *
     * @return string
     */
    protected function getCredentialsConfigValue($configFieldName)
    {
        /** @var string $configPath */
        $configPath = $this->configSection . '/' . $this->credentialsConfigGroup . '/' . $configFieldName;
        /** @var string $configValue */
        $configValue = $this->getConfigValue($configPath, true);

        return $configValue;
    }

    /**
     * Retrieve General config value
     *
     * @param string $configFieldName
     *
     * @return string
     */
    protected function getGeneralConfigValue($configFieldName)
    {
        /** @var string $configPath */
        $configPath = $this->configSection . '/' . $this->generalConfigGroup . '/' . $configFieldName;
        /** @var string $configValue */
        $configValue = $this->getConfigValue($configPath);

        return $configValue;
    }

    /**
     * Retrieve product config value
     *
     * @param string $configFieldName
     *
     * @return string
     */
    protected function getProductConfigValue($configFieldName)
    {
        /** @var string $configPath */
        $configPath = $this->configSection . '/' . $this->productConfigGroup . '/' . $configFieldName;
        /** @var string $configValue */
        $configValue = $this->getConfigValue($configPath);

        return $configValue;
    }

    /**
     * Retrieve product config value
     *
     * @param string $configFieldName
     *
     * @return string
     */
    protected function getProductFilterConfigValue($configFieldName)
    {
        /** @var string $configPath */
        $configPath = $this->configSection . '/' . $this->productFilterConfigGroup . '/' . $configFieldName;
        /** @var string $configValue */
        $configValue = $this->getConfigValue($configPath);

        return $configValue;
    }

    /**
     * Retrieve API Url from config
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->getCredentialsConfigValue($this->baseUrlConfigField);
    }

    /**
     * Retrieve Akeneo Version from config
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->getGeneralConfigValue($this->versionConfigField);
    }

    /**
     * Retrieve API Client ID from config
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->getCredentialsConfigValue($this->clientIdConfigField);
    }

    /**
     * Retrieve API Secret from config
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->getCredentialsConfigValue($this->secretConfigField);
    }

    /**
     * Retrieve Akeneo User from config
     *
     * @return string
     */
    public function getUser()
    {
        return $this->getCredentialsConfigValue($this->userConfigField);
    }

    /**
     * Retrieve Akeneo Pass from config
     *
     * @return string
     */
    public function getPass()
    {
        return $this->getCredentialsConfigValue($this->passConfigField);
    }

    /**
     * Retrieve pagination size from config
     *
     * @return int
     */
    public function getPaginationSize()
    {
        /** @var string $paginationSize */
        $paginationSize = $this->getGeneralConfigValue($this->paginationSizeConfigField);
        if (empty($paginationSize)) {
            $paginationSize = $this->defaultPaginationSize;
        }

        return $paginationSize;
    }

    /**
     * Retrieve website mapping
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getWebsiteMapping()
    {
        /** @var string $adminChannel */
        $adminChannel = $this->getGeneralConfigValue($this->adminWebsiteChannelConfigField);
        if (empty($adminChannel)) {
            return [];
        }
        /** @var mixed[] $fullMapping */
        $fullMapping = [
            [
                'channel' => $adminChannel,
                'website' => Mage::app()->getWebsite(0)->getCode(),
            ],
        ];
        /** @var string $mapping */
        $mapping = $this->getGeneralConfigValue($this->websiteMappingConfigField);
        if (empty($mapping)) {
            return $fullMapping;
        }

        /** @var Mage_Core_Helper_UnserializeArray $unserializeHelper */
        $unserializeHelper = Mage::helper('core/unserializeArray');
        /** @var mixed[] $mapping */
        $mapping = $unserializeHelper->unserialize($mapping);
        if (!empty($mapping) && is_array($mapping)) {
            $fullMapping = array_merge($fullMapping, $mapping);
        }

        return $fullMapping;
    }

    /**
     * Check whether community version is selected
     *
     * @return bool
     */
    public function isCommunityVersion()
    {
        return $this->getVersion() === Pimgento_Api_Model_Adminhtml_System_Config_Source_Version::COMMUNITY_VALUE;
    }

    /**
     * Check whether enterprise version is selected
     *
     * @return bool
     */
    public function isEnterpriseVersion()
    {
        return $this->getVersion() === Pimgento_Api_Model_Adminhtml_System_Config_Source_Version::ENTERPRISE_VALUE;
    }

    /**
     * Is reindex enabled for current import
     *
     * @param string $importCode
     *
     * @return string
     */
    public function isReindexEnabled($importCode)
    {
        if (!is_string($importCode) || empty($importCode)) {
            return '';
        }
        /** @var string $configPath */
        $configPath = sprintf('%s/%s/%s', $this->configSection, $importCode, $this->reindexEnabledConfigField);

        return $this->getConfigValue($configPath);
    }

    /**
     * Is cache clear enabled for current import
     *
     * @param string $importCode
     *
     * @return string
     */
    public function isCacheClearEnabled($importCode)
    {
        if (!is_string($importCode) || empty($importCode)) {
            return '';
        }
        /** @var string $configPath */
        $configPath = sprintf('%s/%s/%s', $this->configSection, $importCode, $this->cacheClearEnabledConfigField);

        return $this->getConfigValue($configPath);
    }

    /**
     * Retrieve current import required cache list
     *
     * @param string $importCode
     *
     * @return string
     */
    public function getCacheList($importCode)
    {
        if (!is_string($importCode) || empty($importCode)) {
            return '';
        }
        /** @var string $configPath */
        $configPath = sprintf('%s/%s/%s', $this->configSection, $importCode, $this->cacheListConfigField);

        return $this->getConfigValue($configPath);
    }

    /**
     * Retrieve Additional Attribute Types config
     *
     * @return mixed[]
     */
    public function getAdditionalAttributeTypesMapping()
    {
        /** @var mixed[] $mapping */
        $mapping = [];
        /** @var string $configPath */
        $configPath = sprintf('%s/%s/%s', $this->configSection, $this->attributeConfigGroup, $this->additionalAttributeTypes);
        /** @var string $matches */
        $matches = $this->getConfigValue($configPath);
        if (empty($matches)) {
            return $mapping;
        }

        /** @var string[] $matches */
        $matches = unserialize($matches);
        if (!is_array($matches)) {
            return $mapping;
        }

        /** @var string[] $match */
        foreach ($matches as $match) {
            if (!isset($match['akeneo_type'], $match['magento_type'])) {
                continue;
            }

            if (!isset($mapping[$match['akeneo_type']])) {
                $mapping[$match['akeneo_type']] = [];
            }

            $mapping[$match['akeneo_type']] = $match['magento_type'];
        }

        return $mapping;
    }

    /**
     * Retrieve reserved attributes prefix usage flag
     *
     * @return bool
     */
    public function isPrefixEnabled()
    {
        /** @var string $configPath */
        $configPath = sprintf('%s/%s/%s', $this->configSection, $this->attributeConfigGroup, $this->prefixReservedConfigField);
        /** @var string $matches */
        $isEnabled = $this->getConfigValue($configPath);
        if (empty($isEnabled)) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve product attribute mapping
     *
     * @return mixed[]
     */
    public function getProductAttributeMapping()
    {
        /** @var mixed[] $mapping */
        $mapping = [];
        /** @var string $matches */
        $matches = $this->getProductConfigValue($this->productAttributeMappingConfigField);

        if (!$matches) {
            return $mapping;
        }
        /** @var mixed $matches */
        $matches = unserialize($matches);

        if (!is_array($matches)) {
            return $mapping;
        }

        /** @var string[] $match */
        foreach ($matches as $match) {
            if (!isset($match['pim_attribute'], $match['magento_attribute'])) {
                continue;
            }

            if (!isset($mapping[$match['pim_attribute']])) {
                $mapping[$match['pim_attribute']] = [];
            }

            $mapping[$match['pim_attribute']][] = $match['magento_attribute'];
        }

        return $mapping;
    }

    /**
     * Retrieve product default tax
     *
     * @return int
     */
    public function getProductTaxId()
    {
        /** @var string $taxId */
        $taxId = $this->getProductConfigValue($this->productTaxIdConfigField);

        if (!is_numeric($taxId)) {
            return 0;
        }

        return (int)$taxId;
    }

    /**
     * Retrieve product default tax
     *
     * @return string[]
     */
    public function getProductConfigurableAttributes()
    {
        /** @var string $attributes */
        $attributes = $this->getProductConfigValue($this->productConfigurableAttributesConfigField);

        if (empty($attributes)) {
            return [];
        }

        /** @var mixed $attributes */
        $attributes = unserialize($attributes);

        if (!is_array($attributes)) {
            return [];
        }

        return $attributes;
    }

    /**
     * Check if image import is enabled
     *
     * @return bool
     */
    public function isMediaImportEnabled()
    {
        /** @var string $enabled */
        $enabled = $this->getProductConfigValue($this->productImageEnabledConfigField);
        if (!$enabled) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve media attribute columns
     *
     * @return string[]
     */
    public function getMediaImportGalleryColumns()
    {
        /** @var string[] $images */
        $images = [];
        /** @var string $attributes */
        $attributes = $this->getProductConfigValue($this->productImageGalleryAttributesConfigField);

        if (!$attributes) {
            return $images;
        }

        /** @var mixed $attributes */
        $attributes = unserialize($attributes);

        if (!is_array($attributes)) {
            return $images;
        }

        /** @var string[] $image */
        foreach ($attributes as $image) {
            if (!isset($image['attribute'])) {
                continue;
            }
            $images[] = $image['attribute'];
        }

        return $images;
    }

    /**
     * Retrieve Media Import Images
     *
     * @return string[]
     * @throws Mage_Core_Exception
     */
    public function getMediaImportImagesColumns()
    {
        /** @var string $attributes */
        $attributes = $this->getProductConfigValue($this->productImageImagesAttributesConfigField);

        if (!$attributes) {
            return [];
        }

        /** @var mixed $attributes */
        $attributes = unserialize($attributes);

        if (!is_array($attributes)) {
            return [];
        }

        /** @var Mage_Eav_Model_Entity_Attribute $entityAttribute */
        $entityAttribute = Mage::getModel('eav/entity_attribute');

        /**
         * @var int     $key
         * @var mixed[] $data
         */
        foreach ($attributes as $key => $data) {
            if (!isset($data['attribute'])) {
                continue;
            }

            /** @var Mage_Eav_Model_Entity_Attribute $attribute */
            $attribute = $entityAttribute->loadByCode(Mage_Catalog_Model_Product::ENTITY, $data['attribute']);
            if (!$attribute->hasData()) {
                continue;
            }

            $attributes[$key]['attribute'] = $attribute->getId();
        }

        return $attributes;
    }

    /**
     * Check if image import is enabled
     *
     * @return bool
     */
    public function isAssetImportEnabled()
    {
        /** @var string $enabled */
        $enabled = $this->getProductConfigValue($this->productAssetEnabledConfigField);
        if (!$enabled) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve asset attribute columns
     *
     * @return string[]
     */
    public function getAssetImportGalleryColumns()
    {
        /** @var string[] $assets */
        $assets = [];
        /** @var string $attributes */
        $attributes = $this->getProductConfigValue($this->productAssetGalleryAttributesConfigField);

        if (!$attributes) {
            return $assets;
        }

        /** @var mixed $attributes */
        $attributes = unserialize($attributes);

        if (!is_array($attributes)) {
            return $assets;
        }

        /** @var string[] $asset */
        foreach ($attributes as $asset) {
            if (!isset($asset['attribute'])) {
                continue;
            }
            $assets[] = $asset['attribute'];
        }

        return $assets;
    }

    /**
     * Retrieve the filter mode used
     *
     * @see Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Mode
     *
     * @return string
     */
    public function getFilterMode()
    {
        return $this->getProductFilterConfigValue($this->productsFiltersMode);
    }

    /**
     * Retrieve if completeness filter is enabled
     *
     * @return bool
     */
    public function getCompletenessEnabled()
    {
        return (bool)$this->getProductFilterConfigValue($this->productsFiltersCompletenessEnabled);
    }

    /**
     * Retrieve the type of filter to apply on the completeness
     *
     * @see Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Completeness
     *
     * @return string
     */
    public function getCompletenessTypeFilter()
    {
        return $this->getProductFilterConfigValue($this->productsFiltersCompletenessType);
    }

    /**
     * Retrieve the value to filter the completeness
     *
     * @return string
     */
    public function getCompletenessValueFilter()
    {
        return $this->getProductFilterConfigValue($this->productsFiltersCompletenessValue);
    }

    /**
     * Retrieve the scope to apply the completeness filter on
     *
     * @return string
     */
    public function getCompletenessScopeFilter()
    {
        return $this->getProductFilterConfigValue($this->productsFiltersCompletenessScope);
    }

    /**
     * Retrieve the locales to apply the completeness filter on
     *
     * @return string
     */
    public function getCompletenessLocalesFilter()
    {
        return $this->getProductFilterConfigValue($this->productsFiltersCompletenessLocales);
    }

    /**
     * Retrieve the status filter
     *
     * @see Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Status
     *
     * @return string
     */
    public function getStatusFilter()
    {
        return $this->getProductFilterConfigValue($this->productsFiltersStatus);
    }

    /**
     * Retrieve the updated filter
     *
     * @return string
     */
    public function getUpdatedFilter()
    {
        return $this->getProductFilterConfigValue($this->productsFiltersUpdated);
    }

    /**
     * Retrieve the families to filter the products on
     *
     * @return string
     */
    public function getFamiliesFilter()
    {
        return $this->getProductFilterConfigValue($this->productsFiltersFamilies);
    }

    /**
     * Retrieve the advance filters
     *
     * @return mixed[]
     */
    public function getAdvancedFilters()
    {
        /** @var string $filters */
        $filters = $this->getProductFilterConfigValue($this->productsFiltersAdvancedFilter);

        if (!$filters) {
            return [];
        }

        /** @var mixed[] $filters */
        $filters = json_decode($filters);

        return (array)$filters;
    }
}
