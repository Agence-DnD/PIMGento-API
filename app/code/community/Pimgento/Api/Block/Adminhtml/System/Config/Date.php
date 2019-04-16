<?php

/**
 * Class Pimgento_Api_Block_Adminhtml_System_Config_Date
 *
 * @category  Class
 * @package   Pimgento_Api_Block_Adminhtml_System_Config_Date
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Pimgento_Api_Block_Adminhtml_System_Config_Date extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        /** @var Varien_Data_Form_Element_Date $date */
        $date = new Varien_Data_Form_Element_Date;
        /** @var string $format */
        $format = 'yyyy-M-d';
        /** @var string[] $data */
        $data = [
            'name'    => $element->getName(),
            'html_id' => $element->getId(),
            'image'   => $this->getSkinUrl('images/grid-cal.gif'),
        ];
        $date->setData($data);
        $date->setValue($element->getValue(), $format);
        $date->setFormat('yyyy-M-d');
        $date->setForm($element->getForm());

        return $date->getElementHtml();
    }
}
