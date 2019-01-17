<?php

/**
 * Class Pimgento_API_Model_System_Config_Source_Cache_Type
 *
 * @category  Class
 * @package   Pimgento_API_Model_System_Config_Source_Cache_Type
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_API_Model_System_Config_Source_Cache_Type
{
    /**
     * Return cache types as options array
     *
     * @return mixed[]
     */
    public function toOptionArray()
    {
        /** @var mixed[] $options */
        $options = [];
        /** @var Varien_Object $type */
        foreach (Mage::app()->getCacheInstance()->getTypes() as $type) {
            $options[] = ['value' => $type->getId(), 'label' => $type->getCacheType()];
        }

        return $options;
    }

    /**
     * Return cache types as array
     *
     * @return string[]
     */
    public function toArray()
    {
        /** @var string[] $options */
        $options = [];
        /** @var Varien_Object $type */
        foreach (Mage::app()->getCacheInstance()->getTypes() as $type) {
            $options[$type->getId()] = $type->getCacheType();
        }

        return $options;
    }
}
