<?php

/**
 * Class Pimgento_Api_Block_Adminhtml_System_Configurable
 *
 * @category  Class
 * @package   Pimgento_Api_Block_Adminhtml_System_Configurable
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Block_Adminhtml_System_Configurable extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * Pimgento_Api_Block_Adminhtml_System_Configurable Constructor
     */
    public function __construct()
    {
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = Mage::helper('pimgento_api');

        $this->addColumn(
            'attribute',
            [
                'label' => $helper->__('Pim Attribute'),
                'style' => 'width:120px',
            ]
        );
        $this->addColumn(
            'value',
            [
                'label' => $helper->__('Value'),
                'style' => 'width:120px',
            ]
        );

        $this->_addAfter = false;

        $this->_addButtonLabel = $helper->__('Add');

        parent::__construct();
    }
}
