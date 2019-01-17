<?php

/**
 * Class Pimgento_Api_Helper_Adminhtml_System_Config_Version
 *
 * @category  Class
 * @package   Pimgento_Api_Helper_Adminhtml_System_Config_Version
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Helper_Adminhtml_System_Config_Version extends Mage_Core_Helper_Abstract
{
    /**
     * Get module version number
     *
     * @param null $moduleName
     *
     * @return string|null
     */
    public function getModuleVersion($moduleName = null)
    {
        if ($moduleName === null) {
            /** @var string $moduleName */
            $moduleName = $this->_getModuleName();
        }

        if (!Mage::getConfig()->getNode(sprintf('modules/%s', $moduleName))) {
            return null;
        }

        return (string)Mage::getConfig()->getModuleConfig($moduleName)->version;
    }
}
