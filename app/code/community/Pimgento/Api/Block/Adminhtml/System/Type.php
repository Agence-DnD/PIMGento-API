<?php

/**
 * Class Pimgento_Api_Block_Adminhtml_System_Type
 *
 * @category  Class
 * @package   Pimgento_Api_Block_Adminhtml_System_Type
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Block_Adminhtml_System_Type extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * Pimgento_Api_Block_Adminhtml_System_Type constructor
     */
    public function __construct()
    {
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = Mage::helper('pimgento_api');

        $this->addColumn(
            'akeneo_type',
            [
                'label' => $helper->__('Pim'),
                'style' => 'width:120px',
            ]
        );

        /** @var Mage_Adminhtml_Block_Html_Select $renderer */
        $renderer = $this->getMagentoTypeColumnRenderer();
        $this->addColumn(
            'magento_type',
            [
                'renderer' => $renderer,
                'label'    => $helper->__('Magento'),
                'style'    => 'width:120px',
            ]
        );

        $this->_addAfter       = false;
        $this->_addButtonLabel = $helper->__('Add');

        parent::__construct();
    }

    /**
     * Get magento attribute type column renderer
     *
     * @return Pimgento_Api_Block_Adminhtml_Source_Select
     */
    protected function getMagentoTypeColumnRenderer()
    {
        /** @var Mage_Eav_Model_Adminhtml_System_Config_Source_Inputtype $inputType */
        $inputType = Mage::getModel('eav/adminhtml_system_config_source_inputtype');
        /** @var Mage_Eav_Model_Adminhtml_System_Config_Source_Inputtype $options */
        $options   = $inputType->toOptionArray();
        $options[] = ['value' => 'price', 'label' => Mage::helper('pimgento_api')->__('Price')];

        /** @var Pimgento_Api_Block_Adminhtml_Source_Select_Type $renderer */
        $renderer = Mage::getBlockSingleton('pimgento_api/adminhtml_source_select_type');
        /** @var string $style */
        $style = 'width:120px';
        $renderer->setId('type-select')->setClass('type-select')->setTitle('type-select')->setOptions($options)->setStyle($style);

        return $renderer;
    }
}
