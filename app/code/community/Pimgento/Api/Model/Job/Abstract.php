<?php

/**
 * Class Pimgento_Api_Model_Job_Abstract
 *
 * @category  Class
 * @package   Pimgento_Api_Model_Job_Abstract
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
abstract class Pimgento_Api_Model_Job_Abstract
{
    /**
     * Import code
     *
     * @var string $code
     */
    protected $code;
    /**
     * Resource model
     *
     * @var Pimgento_Api_Model_Resource_Entities $resource
     */
    protected $resource;
    /**
     * Import table name
     *
     * @var string $tableName
     */
    protected $tableName;
    /**
     * Resource Entities Model name
     *
     * @var string $resourceEntitiesModel
     */
    protected $resourceEntitiesModel = 'pimgento_api/entities';
    /**
     * Current import Indexer processes list
     *
     * @var $indexerProcesses string[]
     */
    protected $indexerProcesses;
    /**
     * Current import Enterprise Indexer processes list
     * Optional, depends on Enterprise features and import reindexation needs
     *
     * @var  string[]
     */
    protected $enterpriseIndexerProcesses;

    /**
     * Retrieve Import code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Retrieve Client
     *
     * @return \Akeneo\Pim\ApiClient\AkeneoPimClientInterface|\Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface
     * @throws Mage_Core_Exception
     * @throws Pimgento_Api_Exception
     */
    public function getClient()
    {
        /** @var Pimgento_Api_Helper_Client $helperClient */
        $helperClient = Mage::helper('pimgento_api/client');

        return $helperClient->getApiClient();
    }

    /**
     * Retrieve helper
     *
     * @return Pimgento_Api_Helper_Data
     */
    public function getHelper()
    {
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = Mage::helper('pimgento_api');

        return $helper;
    }

    /**
     * Retrieve entities resource model
     *
     * @return Pimgento_Api_Model_Resource_Entities
     */
    public function getResourceEntities()
    {
        if (empty($this->resource)) {
            /** @var Pimgento_Api_Model_Resource_Entities $resource */
            $resource = Mage::getResourceModel($this->resourceEntitiesModel);
            $resource->setEntityCode($this->getCode());
            $this->resource = $resource;
        }

        return $this->resource;
    }

    /**
     * Return import table name
     *
     * @return string
     * @throws Pimgento_Api_Exception
     */
    public function getTableName()
    {
        if (empty($this->tableName)) {
            $this->tableName = $this->getResourceEntities()->getTableName();
        }

        return $this->tableName;
    }

    /**
     * Retrieve configuration helper
     *
     * @return Pimgento_Api_Helper_Configuration
     */
    public function getConfigurationHelper()
    {
        /** @var Pimgento_Api_Helper_Configuration $configurationHelper */
        $configurationHelper = Mage::helper('pimgento_api/configuration');

        return $configurationHelper;
    }

    /**
     * Reindex
     * Optional, depends on configuration
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     */
    public function reindex($task)
    {
        if (empty($this->getConfigurationHelper()->isReindexEnabled($this->getCode()))) {
            $task->setStepWarning($this->getHelper()->__('Reindex is disabled'));
            $task->setStepMessage($this->getHelper()->__('Step skipped.'));

            return;
        }

        /** @var Mage_Index_Model_Indexer $indexer */
        $indexer = Mage::getSingleton('index/indexer');

        /** @var string[] $processes */
        $processes = $this->indexerProcesses;

        if (Mage::getEdition() !== Mage::EDITION_ENTERPRISE && !empty($this->enterpriseIndexerProcesses)) {
            $processes = array_merge($processes, $this->enterpriseIndexerProcesses);
        }

        /** @var string $code */
        foreach ($processes as $code) {
            /** @var Mage_Index_Model_Process $process */
            $process = $indexer->getProcessByCode($code);
            if (empty($process)) {
                continue;
            }

            $process->reindexEverything();
        }

        $task->setStepMessage(
            $this->getHelper()->__('Reindex successful for %s import', $this->getCode())
        );
    }

    /**
     * Clear cache
     *
     * @param Task_Executor_Model_Task $task
     *
     * @return void
     */
    public function cleanCache($task)
    {
        if (empty($this->getConfigurationHelper()->isCacheClearEnabled($this->getCode()))) {
            $task->setStepWarning($this->getHelper()->__('Cache cleaning is disabled'));
            $task->setStepMessage($this->getHelper()->__('Step skipped.'));

            return;
        }
        /** @var string[] $cacheTypes */
        $cacheTypes = $this->getConfigurationHelper()->getCacheList($this->getCode());
        if (empty($cacheTypes)) {
            $task->setStepWarning($this->getHelper()->__('No cache to clear for import: %s', $this->getCode()));
            $task->setStepMessage($this->getHelper()->__('Step skipped.'));

            return;
        }

        /** @var string $cacheTypeList */
        $cacheTypeList = explode(',', $cacheTypes);
        /** @var string $cacheType */
        foreach ($cacheTypes as $cacheType) {
            Mage::app()->getCacheInstance()->cleanType($cacheType);
        }

        $task->setStepMessage(
            $this->getHelper()->__('Cache cleaned: %s', $cacheTypeList)
        );
    }
}
