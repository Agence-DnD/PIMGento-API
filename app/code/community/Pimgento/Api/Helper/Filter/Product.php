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
class Pimgento_Api_Helper_Filter_Product extends Mage_Core_Helper_Abstract
{
    /**
     * Search Builder
     *
     * @var \Akeneo\Pim\ApiClient\Search\SearchBuilder $searchBuilder
     */
    protected $searchBuilder = null;
    
    /**
     * Get the filters for the product API query
     *
     * @return array
     */
    public function getFilters()
    {
        /** @var string $mode */
        $mode = $this->getConfigHelper()->getFilterMode();
        if ($mode == Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Mode::ADVANCED_VALUE) {
            return $this->getConfigHelper()->getAdvancedFilters();
        }
        $this->addCompletenessFilter();
        $this->addStatusFilter();
        $this->addFamiliesFilter();
        $this->addUpdatedFilter();
        /** @var array $filters */
        $filters = $this->getSearchBuilder()->getFilters();
        if (empty($filters)) {
            return [];
        }

        return ['search' => $filters];
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

        /** @var string $filterType */
        $filterType = $this->getConfigHelper()->getCompletenessTypeFilter();
        /** @var string $filterValue */
        $filterValue = $this->getConfigHelper()->getCompletenessValueFilter();
        /** @var mixed $locales */
        $locales = $this->getConfigHelper()->getCompletenessLocalesFilter();
        $locales = explode(',', $locales);
        /** @var string $scope */
        $scope = $this->getConfigHelper()->getCompletenessScopeFilter();
        /** @var string[] $options */
        $options = ['scope' => $scope];

        /** @var array $localesType */
        $localesType = [
            Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Completeness::LOWER_OR_EQUALS_THAN_ON_ALL_LOCALES,
            Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Completeness::LOWER_THAN_ON_ALL_LOCALES,
            Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Completeness::GREATER_THAN_ON_ALL_LOCALES,
            Pimgento_Api_Model_Adminhtml_System_Config_Source_Filters_Completeness::GREATER_OR_EQUALS_THAN_ON_ALL_LOCALES
        ];
        if (in_array($filterType, $localesType)) {
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
        /** @var string $filter */
        $filter = $this->getConfigHelper()->getUpdatedFilter();
        if (!is_numeric($filter)) {
            return;
        }
        $this->getSearchBuilder()->addFilter('updated', 'SINCE LAST N DAYS', (int)$filter);

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
     * Retrieve configuration helper
     *
     * @return Pimgento_Api_Helper_Configuration
     */
    protected function getConfigHelper()
    {
        /** @var Pimgento_Api_Helper_Configuration $configHelper */
        $configHelper = Mage::helper('pimgento_api/configuration');
        
        return $configHelper;
    }
}
