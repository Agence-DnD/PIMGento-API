<?php

/**
 * Class Task_Log_Model_Task
 *
 * @category  Class
 * @package   Task_Log
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Log_Model_Task extends Mage_Core_Model_Abstract
{

    /**
     * @var int TASK_STATUS_PROCESSING
     */
    const TASK_STATUS_PROCESSING = 1;
    /**
     * @var int TASK_STATUS_SUCCESS
     */
    const TASK_STATUS_SUCCESS = 2;
    /**
     * @var int TASK_STATUS_ERROR
     */
    const TASK_STATUS_ERROR = 3;

    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('task_log/task');
    }

    /**
     * Add step to task
     *
     * @param array $data
     *
     * @return Task_Log_Model_Task
     */
    public function addStep($data)
    {
        /** @var Task_Log_Model_Resource_Task $resource */
        $resource = $this->_getResource();

        $resource->addStep($data);

        return $this;
    }

    /**
     * Retrieve all steps
     *
     * @return array
     */
    public function getSteps()
    {
        /** @var Task_Log_Model_Resource_Task $resource */
        $resource = $this->_getResource();

        return $resource->loadSteps($this->getId());
    }

    /**
     * Processing object before save data
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();

        if ($this->getIsNew()) {
            $this->isObjectNew(true);
        }

        return $this;
    }
}
