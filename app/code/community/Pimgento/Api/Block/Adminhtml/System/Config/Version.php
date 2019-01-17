<?php

/**
 * Class Pimgento_Api_Block_Adminhtml_System_Config_Version
 *
 * @category  Class
 * @package   Pimgento_Api_Block_Adminhtml_System_Config_Version
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Block_Adminhtml_System_Config_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Get element HTML
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return (string)Mage::helper('pimgento_api/adminhtml_system_config_version')->getModuleVersion();
    }
}
