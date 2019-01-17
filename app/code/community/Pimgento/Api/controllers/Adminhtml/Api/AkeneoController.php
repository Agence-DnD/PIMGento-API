<?php

/**
 * Class Pimgento_Api_Adminhtml_Api_AkeneoController
 *
 * @category  Class
 * @package   Pimgento_Api_Adminhtml_Api_AkeneoController
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Pimgento_Api_Adminhtml_Api_AkeneoController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Default index action, forward to test action
     *
     * @return void
     */
    public function indexAction()
    {
        $this->_forward('check');
    }

    /**
     * Check Akeneo Api action
     *
     * @return void
     */
    public function checkAction()
    {
        /** @var Pimgento_Api_Helper_Client $apiHelper */
        $apiHelper = Mage::helper('pimgento_api/client');
        /** @var Mage_Adminhtml_Model_Session $session */
        $session = Mage::getSingleton('adminhtml/session');
        try {
            /** @var string $message */
            $message = $apiHelper->checkApiClient();
        } catch (Pimgento_Api_Exception $exception) {
            /** @var string $message */
            $message = $exception->getMessage();
            $session->addException($exception, $this->_getHelper()->__('Api test failed: %s', $message));

            $this->_redirectUrl($this->_getRefererUrl());

            return;
        }

        $session->addSuccess($message);

        $this->_redirectUrl($this->_getRefererUrl());
    }
}