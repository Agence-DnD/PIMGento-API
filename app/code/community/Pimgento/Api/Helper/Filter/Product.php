<?php

/**
 * Class Pimgento_Api_Helper_Filter_Product
 *
 * @category  Class
 * @package   Pimgento_Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://pimgento.com/
 */
class Pimgento_Api_Helper_Filter_Product extends Pimgento_Api_Helper_Data
{
    /**
     * Search Builder
     *
     * @var \Akeneo\Pim\ApiClient\Search\SearchBuilder $searchBuilder
     */
    protected $searchBuilder = null;
    /**
     * Configuration helper
     *
     * @var null $configHelper
     */
    protected $configHelper = null;

    /**
     * Get the filters for the product API query
     *
     * @return mixed[]|string[]
     * @throws Mage_Core_Exception
     */
    public function getFilters()
    {
        /** @var mixed[] $mappedChannels */
        $mappedChannels = $this->getConfigHelper()->getMappedChannels();
        if (empty($mappedChannels)) {
            /** @var string[] $error */
            $error = [
                'error' => $this->__('No website/channel mapped. Please check your configurations.'),
            ];

            return $error;
        }

        /** @var mixed[] $filters */
        $filters = [];
        /** @var mixed[] $search */
        $search = [];

        /** @var string $mode */
        $mode = $this->getConfigHelper()->getFilterMode();
        if ($mode == Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Mode::ADVANCED_VALUE) {
            /** @var mixed[] $advancedFilters */
            $advancedFilters = $this->getAdvancedFilters();
            if (!empty($advancedFilters['scope'])) {
                if (!in_array($advancedFilters['scope'], $mappedChannels)) {
                    /** @var string[] $error */
                    $error = [
                        'error' => $this->__('Advanced filters contains an unauthorized scope, please add check your filters and website mapping.'),
                    ];

                    return $error;
                }

                return [$advancedFilters];
            }

            $search = $advancedFilters['search'];
        }

        if ($mode == Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Mode::STANDARD_VALUE) {
            $this->addCompletenessFilter();
            $this->addStatusFilter();
            $this->addFamiliesFilter();
            $this->addUpdatedFilter();
            $search = $this->getSearchBuilder()->getFilters();
        }

        /** @var Pimgento_Api_Helper_Store $storeHelper */
        $storeHelper = Mage::helper('pimgento_api/store');

        /** @var string $channel */
        foreach ($mappedChannels as $channel) {
            /** @var string[] $filter */
            $filter = [
                'search' => $search,
                'scope'  => $channel,
            ];

            if ($mode == Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Mode::ADVANCED_VALUE) {
                $filters[] = $filter;

                continue;
            }

            if ($this->getConfigHelper()->getCompletenessEnabled()) {
                /** @var string[] $completeness */
                $completeness = reset($search['completeness']);
                if (!empty($completeness['scope']) && $completeness['scope'] !== $channel) {
                    $completeness['scope']  = $channel;
                    $search['completeness'] = [$completeness];

                    $filter['search'] = $search;
                }
            }

            /** @var string[] $locales */
            $locales = $storeHelper->getChannelStoreLangs($channel);
            if (!empty($locales)) {
                /** @var Pimgento_Api_Helper_Locales $localesHelper */
                $localesHelper = Mage::helper('pimgento_api/locales');
                /** @var string $locales */
                $akeneoLocales = $localesHelper->getAkeneoLocales();;
                if(!empty($akeneoLocales)){
                    $locales = array_intersect($locales, $akeneoLocales);
                }

                /** @var string $locales */
                $locales           = implode(',', $locales);
                $filter['locales'] = $locales;
            }

            $filters[] = $filter;
        }

        return $filters;
    }

    /**
     * Retrieve advanced filters config
     *
     * @return mixed[]
     */
    protected function getAdvancedFilters()
    {
        /** @var mixed[] $filters */
        $filters = $this->getConfigHelper()->getAdvancedFilters();

        return $filters;
    }

    /**
     * Add completeness filter for Akeneo API
     *
     * @return void
     */
    protected function addCompletenessFilter()
    {
        if (!$this->getConfigHelper()->getCompletenessEnabled()) {
            return;
        }

        /** @var string $scope */
        $scope = $this->getConfigHelper()->getAdminDefaultChannel();
        /** @var mixed[] $options */
        $options = ['scope' => $scope];

        /** @var string $filterType */
        $filterType = $this->getConfigHelper()->getCompletenessTypeFilter();
        /** @var string $filterValue */
        $filterValue = $this->getConfigHelper()->getCompletenessValueFilter();

        /** @var string[] $localesType */
        $localesType = [
            Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Completeness::LOWER_OR_EQUALS_THAN_ON_ALL_LOCALES,
            Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Completeness::LOWER_THAN_ON_ALL_LOCALES,
            Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Completeness::GREATER_THAN_ON_ALL_LOCALES,
            Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Completeness::GREATER_OR_EQUALS_THAN_ON_ALL_LOCALES,
        ];

        if (in_array($filterType, $localesType)) {
            /** @var mixed $locales */
            $locales = $this->getConfigHelper()->getCompletenessLocalesFilter();
            /** @var string[] $locales */
            $locales            = explode(',', $locales);
            $options['locales'] = $locales;
        }

        $this->getSearchBuilder()->addFilter('completeness', $filterType, $filterValue, $options);

        return;
    }

    /**
     * Add status filter for Akeneo API
     *
     * @return void
     */
    protected function addStatusFilter()
    {
        /** @var string $filter */
        $filter = $this->getConfigHelper()->getStatusFilter();
        if ($filter === Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Status::STATUS_NO_CONDITION) {
            return;
        }
        if ($filter === '2') {
            $filter = 0;
        }
        $this->getSearchBuilder()->addFilter('enabled', '=', (bool)$filter);

        return;
    }

    /**
     * Add updated filter for Akeneo API
     *
     * @return void
     */
    protected function addUpdatedFilter()
    {
        /** @var string $mode */
        $mode = $this->getConfigHelper()->getUpdatedMode();

        if ($mode == Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Update::BETWEEN) {
            /** @var datetime $dateLower */
            $dateAfter = $this->getConfigHelper()->getUpdatedBetweenAfterFilter() . ' 00:00:00';
            /** @var datetime $dateUpper */
            $dateBefore = $this->getConfigHelper()->getUpdatedBetweenBeforeFilter() . ' 23:59:59';
            if (empty($dateAfter) || empty($dateBefore)) {
                return;
            }
            /** @var datetime[] $dates */
            $dates = [$dateAfter, $dateBefore];
            $this->getSearchBuilder()->addFilter('updated', $mode, $dates);
        }
        if ($mode == Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Update::SINCE_LAST_N_DAYS) {
            /** @var string $filter */
            $filter = $this->getConfigHelper()->getUpdatedSinceFilter();
            if (!is_numeric($filter)) {
                return;
            }
            $this->getSearchBuilder()->addFilter('updated', $mode, (int)$filter);
        }
        if ($mode == Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Update::LOWER_THAN) {
            /** @var string $date */
            $date = $this->getConfigHelper()->getUpdatedLowerFilter();
            if (empty($date)) {
                return;
            }
            $date = $date . ' 23:59:59';
        }
        if ($mode == Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Update::GREATER_THAN) {
            $date = $this->getConfigHelper()->getUpdatedGreaterFilter();
            if (empty($date)) {
                return;
            }
            $date = $date . ' 00:00:00';
        }
        if (!empty($date)) {
            $this->getSearchBuilder()->addFilter('updated', $mode, $date);
        }
        return;
    }

    /**
     * Add families filter for Akeneo API
     *
     * @return void
     */
    protected function addFamiliesFilter()
    {
        /** @var mixed $filter */
        $filter = $this->getConfigHelper()->getFamiliesFilter();
        if (!$filter) {
            return;
        }

        /** @var string[] $filter */
        $filter = explode(',', $filter);

        $this->getSearchBuilder()->addFilter('family', 'NOT IN', $filter);

        return;
    }

    /**
     * Retrieve Search Builder
     *
     * @return \Akeneo\Pim\ApiClient\Search\SearchBuilder
     */
    protected function getSearchBuilder()
    {
        if ($this->searchBuilder === null) {
            /** @var Pimgento_Api_Helper_Client $clientHelper */
            $clientHelper = Mage::helper('pimgento_api/client');

            $this->searchBuilder = $clientHelper->getSearchBuilder();
        }

        return $this->searchBuilder;
    }

    /**
     * Get active product filter mode
     *
     * @return string
     */
    public function getFilterMode()
    {
        return $this->getConfigHelper()->getFilterMode();
    }

    /**
     * Retrieve configuration helper
     *
     * @return Pimgento_Api_Helper_Configuration
     */
    protected function getConfigHelper()
    {
        if ($this->configHelper === null) {
            /** @var Pimgento_Api_Helper_Configuration $configHelper */
            $configHelper = Mage::helper('pimgento_api/configuration');

            $this->configHelper = $configHelper;
        }

        return $this->configHelper;
    }
}
