<?php

if (file_exists(Mage::getBaseDir() . DS . 'vendor' . DS . 'autoload.php')) {
    require_once Mage::getBaseDir() . DS . 'vendor' . DS . 'autoload.php';
}

/**
 * Class Pimgento_Api_Helper_Client
 *
 * @category  Class
 * @package   Pimgento_Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Helper_Client extends Mage_Core_Helper_Data
{
    /**
     * Vendor Autoload path
     *
     * @var string VENDOR_AUTOLOAD_PATH
     */
    const VENDOR_AUTOLOAD_PATH = 'vendor' . DS . 'autoload.php';
    /**
     * API Client object
     *
     * @var \Akeneo\Pim\ApiClient\AkeneoPimClientInterface $client
     */
    protected $client = null;

    /**
     * Search Builder
     *
     * @var \Akeneo\Pim\ApiClient\Search\SearchBuilder $searchBuilder
     */
    protected $searchBuilder = null;

    /**
     * Instantiate Akeneo API PHP Client
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    protected function init()
    {
        /** @var Pimgento_Api_Helper_Configuration $configurationHelper */
        $configurationHelper = $this->getConfigurationHelper();

        try {
            $this->checkAutoload();
            /** @var \Akeneo\Pim\ApiClient\AkeneoPimClientBuilder $clientBuilder */
            $clientBuilder = $this->getClientBuilder();
            /** @var \Akeneo\Pim\ApiClient\AkeneoPimClientInterface $client */
            $client = $clientBuilder->buildAuthenticatedByPassword(
                $configurationHelper->getClientId(),
                $configurationHelper->getSecret(),
                $configurationHelper->getUser(),
                $configurationHelper->getPass()
            );
        } catch (Pimgento_Api_Exception $exception) {
            throw new Pimgento_Api_Exception(
                $this->__('Akeneo Api client instantiation failed: %s', $exception->getMessage())
            );
        }

        if (!$client) {
            throw new Pimgento_Api_Exception($this->__('Akeneo client authentication failed'));
        }

        $this->client = $client;
    }

    /**
     * Check autoload file and permissions
     *
     * @return void
     * @throws Pimgento_Api_Exception
     */
    protected function checkAutoload()
    {
        /** @var string $autoload */
        $autoload = Mage::getBaseDir() . DS . self::VENDOR_AUTOLOAD_PATH;
        if (!is_file($autoload) || !is_readable($autoload)) {
            throw new Pimgento_Api_Exception(
                $this->__('Autoloader file %s doesn\'t exist or is not readable', $autoload)
            );
        }
    }

    /**
     * Retrieve configuration helper
     *
     * @return Pimgento_Api_Helper_Configuration
     */
    protected function getConfigurationHelper()
    {
        /** @var Pimgento_Api_Helper_Configuration $helper */
        $helper = Mage::helper('pimgento_api/configuration');

        return $helper;
    }

    /**
     * Retriever Client Builder class name from Akeneo version
     *
     * @return string
     * @throws Pimgento_Api_Exception
     */
    protected function getClientBuilderClassName()
    {
        /** @var Pimgento_Api_Helper_Configuration $configurationHelper */
        $configurationHelper = $this->getConfigurationHelper();
        if (!$configurationHelper->isCommunityVersion() && !$configurationHelper->isEnterpriseVersion()) {
            throw new Pimgento_Api_Exception($this->__('Please select an Akeneo version in Api configuration'));
        }
        /** @var string $clientBuilderClassName */
        $clientBuilderClassName = '\Akeneo\Pim\ApiClient\AkeneoPimClientBuilder';
        if ($configurationHelper->isEnterpriseVersion()) {
            $clientBuilderClassName = '\Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder';
        }

        return $clientBuilderClassName;
    }

    /**
     * Retrieve client builder
     *
     * @return \Akeneo\Pim\ApiClient\AkeneoPimClientBuilder
     * @throws Pimgento_Api_Exception
     */
    protected function getClientBuilder()
    {
        /** @var Pimgento_Api_Helper_Configuration $configurationHelper */
        $configurationHelper = $this->getConfigurationHelper();
        /** @var string $clientBuilderClassName */
        $clientBuilderClassName = $this->getClientBuilderClassName();
        if (!class_exists($clientBuilderClassName)) {
            throw new Pimgento_Api_Exception($this->__('Class %s doesn\'t exist', $clientBuilderClassName));
        }
        /** @var \Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder $clientBuilder */
        $clientBuilder = new $clientBuilderClassName($configurationHelper->getBaseUrl());

        $clientBuilder->setHttpClient(new \Http\Adapter\Guzzle6\Client());
        $clientBuilder->setStreamFactory(new \Http\Message\StreamFactory\GuzzleStreamFactory());
        $clientBuilder->setRequestFactory(new \Http\Message\MessageFactory\GuzzleMessageFactory());

        return $clientBuilder;
    }

    /**
     * Get Akeneo API PHP Client instance
     *
     * @return \Akeneo\Pim\ApiClient\AkeneoPimClientInterface
     * @throws Pimgento_Api_Exception
     */
    public function getApiClient()
    {
        if (empty($this->client)) {
            $this->init();
        }

        return $this->client;
    }

    /**
     * Retrieve Search Builder
     *
     * @return \Akeneo\Pim\ApiClient\Search\SearchBuilder
     */
    public function getSearchBuilder()
    {
        if ($this->searchBuilder === null) {
            $this->searchBuilder = new \Akeneo\Pim\ApiClient\Search\SearchBuilder();
        }

        return $this->searchBuilder;
    }

    /**
     * Check Akeneo API connection
     *
     * @return string
     * @throws Pimgento_Api_Exception
     */
    public function checkApiClient()
    {
        try {
            /** @var \Akeneo\Pim\ApiClient\AkeneoPimClientInterface $client */
            $client = $this->getApiClient();
            $client->getChannelApi()->listPerPage(1);
        } catch (Exception $exception) {
            throw new Pimgento_Api_Exception($exception->getMessage());
        }

        return $this->__('Akeneo Api check successful');
    }
}
