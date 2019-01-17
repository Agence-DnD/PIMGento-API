<?php

/**
 * Class Task_Log_Model_Resource_Task
 *
 * @category  Class
 * @package   Task_Log
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Log_Model_Resource_Task extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Use is object new method for save of object
     *
     * @var boolean
     */
    protected $_useIsObjectNew = true;

    /**
     * Primary key auto increment flag
     *
     * @var bool
     */
    protected $_isPkAutoIncrement = false;

    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('task_log/task', 'task_id');
    }

    /**
     * Add step to task
     *
     * @param mixed[] $data
     *
     * @return Task_Log_Model_Resource_Task
     */
    public function addStep($data)
    {
        $adapter = $this->_getWriteAdapter();

        if (isset($data['task_id'])) {
            $adapter->insert($this->getTable('task_log/step'), $data);
        }

        return $this;
    }

    /**
     * Load task steps
     *
     * @param int $taskId
     *
     * @return string[]
     */
    public function loadSteps($taskId)
    {
        $steps = [];

        if ($taskId) {
            $adapter = $this->_getWriteAdapter();

            $select = $adapter->select()
                ->from($this->getTable('task_log/step'))
                ->where('task_id = ?', $taskId)
                ->order('created_at ASC');

            $steps = $adapter->fetchAll($select);
        }

        return $steps;
    }
}
