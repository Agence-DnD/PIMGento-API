<?php

/**
 * Class Task_Log_Model_Resource_Task_Collection
 *
 * @category  Class
 * @package   Task_Log
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Log_Model_Resource_Task_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('task_log/task');
    }
}
