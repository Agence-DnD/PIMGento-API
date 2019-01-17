<?php

/**
 * Class Pimgento_Api_Model_Entities
 *
 * @category  Class
 * @package   Pimgento_Api_Model_Entities
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Pimgento_Api_Model_Entities extends Mage_Core_Model_Abstract
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('pimgento_api/entities');
    }

    /**
     * Delete entity line from Pimgento main table
     *
     * @param string $import
     * @param int    $entityId
     *
     * @return int|bool
     */
    public function deleteByEntityId($import, $entityId)
    {
        if (empty($import) || !is_string($import) || empty($entityId)) {
            return false;
        }

        /** @var Mage_Core_Model_Resource $resource */
        $resource = Mage::getModel('core/resource');
        /** @var string $pimgentoTable */
        $pimgentoTable = $this->getResource()->getMainTable();

        /** @var string[] $data */
        $data = [
            'import = ?'    => $import,
            'entity_id = ?' => $entityId,
        ];

        return $resource->getConnection('write')->delete($pimgentoTable, $data);
    }
}
