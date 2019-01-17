<?php

/**
 * Class Pimgento_Api_Model_Adminhtml_System_Config_Source_Tax
 *
 * @category  Class
 * @package   Pimgento_Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Adminhtml_System_Config_Source_Tax
{
    /**
     * Options getter
     *
     * @return mixed[]
     */
    public function toOptionArray()
    {
        /** @var Mage_Tax_Model_Resource_Class_Collection $taxes */
        $taxes = $this->getCollection();
        /** @var mixed[] $options */
        $options = [
            [
                'value' => 0,
                'label' => Mage::helper('pimgento_api')->__('None')
            ]
        ];
        /**
         * @var Mage_Tax_Model_Class $tax
         */
        foreach ($taxes as $tax) {
            $options[] = ['value' => $tax->getId(), 'label' => $tax->getClassName()];
        }

        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return string[]
     */
    public function toArray()
    {
        /** @var Mage_Tax_Model_Resource_Class_Collection $taxes */
        $taxes = $this->getCollection();
        /** @var string[] $options */
        $options = [
            '0' => Mage::helper('pimgento_api')->__('None')
        ];
        /**
         * @var Mage_Tax_Model_Class $tax
         */
        foreach ($taxes as $tax) {
            $options[$tax->getId()] = $tax->getClassName();
        }

        return $options;
    }

    /**
     * Retrieve tax collection
     *
     * @return Mage_Tax_Model_Resource_Class_Collection
     */
    protected function getCollection()
    {
        return Mage::getModel('tax/class')->getCollection()->addFieldToFilter('class_type', 'PRODUCT');
    }
}
