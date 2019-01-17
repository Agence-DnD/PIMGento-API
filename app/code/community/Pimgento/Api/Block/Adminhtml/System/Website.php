<?php

/**
 * Class Pimgento_Api_Block_Adminhtml_System_Website
 *
 * @category  Class
 * @package   Pimgento_Api_Block_Adminhtml_System_Website
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Block_Adminhtml_System_Website extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * Pimgento_Api_Block_Adminhtml_System_Website constructor
     */
    public function __construct()
    {
        /** @var Pimgento_Api_Helper_Data $helper */
        $helper = Mage::helper('pimgento_api');

        $this->addColumn(
            'channel',
            [
                'label' => $helper->__('Channel'),
                'class' => 'required-entry',
                'style' => 'width:120px',
            ]
        );

        /** @var Mage_Adminhtml_Block_Html_Select $renderer */
        $renderer = $this->getWebsiteColumnRenderer();
        $this->addColumn(
            'website',
            [
                'renderer' => $renderer,
                'label'    => $helper->__('Website'),
            ]
        );

        $this->_addAfter       = false;
        $this->_addButtonLabel = $helper->__('Add');

        parent::__construct();
    }

    /**
     * Get website column renderer
     *
     * @return Pimgento_Api_Block_Adminhtml_Source_Select
     */
    protected function getWebsiteColumnRenderer()
    {
        /** @var mixed[] $websites */
        $websites = Mage::app()->getWebsites();
        /** @var mixed[] $options */
        $options = [];

        /** @var Mage_Core_Model_Website $website */
        foreach ($websites as $website) {
            $options[] = [
                'value' => $website->getCode(),
                'label' => $website->getCode(),
            ];
        }

        /** @var Pimgento_Api_Block_Adminhtml_Source_Select_Website $renderer */
        $renderer = Mage::getBlockSingleton('pimgento_api/adminhtml_source_select_website');
        /** @var string $style */
        $style = 'width:120px';
        $renderer->setClass('website-select')->setTitle('website-select')->setOptions($options)->setStyle($style);

        return $renderer;
    }
}
