<?php

/**
 * Class Pimgento_Api_Block_Adminhtml_System_Image
 *
 * @category  Class
 * @package   Pimgento_Api_Block_Adminhtml_System_Image
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Block_Adminhtml_System_Image extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * Pimgento_Api_Block_Adminhtml_System_Image Constructor
     */
    public function __construct()
    {
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = Mage::helper('pimgento_api');
        /** @var Mage_Adminhtml_Block_Html_Select $renderer */
        $renderer = $this->getAttributeColumnRenderer();
        $this->addColumn(
            'attribute',
            [
                'renderer' => $renderer,
                'label'    => $helper->__('Magento Attribute'),
            ]
        );

        $this->addColumn(
            'column',
            [
                'label' => $helper->__('Pim Attribute'),
                'style' => 'width:120px',
            ]
        );

        $this->_addAfter = false;

        $this->_addButtonLabel = $helper->__('Add');

        parent::__construct();
    }

    /**
     * Get attribute column renderer
     *
     * @return Pimgento_Api_Block_Adminhtml_Source_Select
     */
    protected function getAttributeColumnRenderer()
    {
        /** @var Mage_Eav_Model_Entity_Attribute $entityAttribute */
        $entityAttribute = Mage::getModel('eav/entity_attribute');
        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Collection $attributes */
        $attributes = $entityAttribute->getCollection()->addFieldToFilter('frontend_input', 'media_image');
        /** @var mixed[] $options */
        $options = [];

        /** @var Mage_Eav_Model_Entity_Attribute $attribute */
        foreach ($attributes as $attribute) {
            $options[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getAttributeCode(),
            ];
        }

        /** @var Pimgento_Api_Block_Adminhtml_Source_Select_Image $renderer */
        $renderer = Mage::getBlockSingleton('pimgento_api/adminhtml_source_select_image');
        /** @var string $style */
        $style = 'style="width:120px"';
        $renderer->setId('attribute-select')->setClass('attribute-select')->setTitle('attribute-select')->setOptions(
            $options
        )->setStyle($style);

        return $renderer;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        /** @var string $render */
        $render = parent::_toHtml();

        return sprintf('<div id="pimgento_api_product_image_images_attributes">%s</div>', $render);
    }
}
