<?php

/**
 * Class Task_Log_Model_Observer
 *
 * @category  Class
 * @package   Task_Log
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Log_Model_Observer
{
    /**
     * Create log when task begins
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Task_Log_Model_Observer
     * @throws Mage_Core_Model_Store_Exception
     */
    public function startTask(Varien_Event_Observer $observer)
    {
        /** @var Task_Executor_Model_Task $task */
        $task = $observer->getEvent()->getTask();

        /** @var Task_Log_Model_Task $log */
        $log = Mage::getModel('task_log/task');

        /** @var Mage_Core_Model_Date $date */
        $date = Mage::getModel('core/date');

        $current = $task->getTask();

        $data = [
            'task_id'    => $task->getTaskId(),
            'command'    => $task->getCommand(),
            'task_label' => $current['label'],
            'status'     => Task_Log_Model_Task::TASK_STATUS_PROCESSING,
            'options'    => serialize($task->getOptions()->toArray()),
            'step_count' => $task->getStepCount(),
            'created_at' => $date->gmtDate(),
            'is_new'     => true,
        ];

        /** @var Task_Log_Helper_Data $helper */
        $helper = Mage::helper('task_log');

        if (($user = $helper->getCurrentAdminUser())) {
            $data['user'] = $user;
        }

        $log->setData($data);
        $log->save();

        return $this;
    }

    /**
     * Update log when task ends
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Task_Log_Model_Observer
     */
    public function endTask(Varien_Event_Observer $observer)
    {
        /** @var Task_Log_Helper_Data $task */
        $task = $observer->getEvent()->getTask();

        /** @var Task_Log_Model_Task $log */
        $log = Mage::getModel('task_log/task');

        $log->load($task->getTaskId());

        if ($log->hasData()) {
            $log->setStatus(Task_Log_Model_Task::TASK_STATUS_SUCCESS)->save();
        }

        return $this;
    }

    /**
     * Add log when step begins
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Task_Log_Model_Observer
     */
    public function startStep(Varien_Event_Observer $observer)
    {
        /** @var Task_Executor_Model_Task $task */
        $task = $observer->getEvent()->getTask();

        /** @var Task_Log_Model_Task $log */
        $log = Mage::getModel('task_log/task');

        /** @var Mage_Core_Model_Date $date */
        $date = Mage::getModel('core/date');

        $log->load($task->getTaskId());
        if ($log->hasData()) {
            /** @var mixed[] $messages */
            $messages[] = $task->getStepComment();
            /** @var mixed[] $data */
            $data = [
                'task_id'    => $task->getTaskId(),
                'number'     => $task->getStepNumber(),
                'messages'   => $this->encodeMessages($messages),
                'created_at' => $date->gmtDate(),
            ];

            $log->addStep($data);
        }

        return $this;
    }

    /**
     * Add log when step ends
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Task_Log_Model_Observer
     */
    public function endStep(Varien_Event_Observer $observer)
    {
        /** @var Task_Executor_Model_Task $task */
        $task = $observer->getEvent()->getTask();
        /** @var Task_Log_Model_Task $log */
        $log = Mage::getModel('task_log/task');
        /** @var Mage_Core_Model_Date $date */
        $date = Mage::getModel('core/date');

        $log->load($task->getTaskId());
        if ($log->hasData()) {
            /** @var mixed[] $messages */
            $messages   = $task->getStepWarnings();
            $messages[] = $task->getStepMessage();
            /** @var mixed[] $data */
            $data = [
                'task_id'    => $task->getTaskId(),
                'number'     => $task->getStepNumber(),
                'messages'   => $this->encodeMessages($messages),
                'created_at' => $date->gmtDate(),
            ];

            $log->addStep($data);
        }

        return $this;
    }

    /**
     * Add log when error is sent
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Task_Log_Model_Observer
     */
    public function error(Varien_Event_Observer $observer)
    {
        /** @var Task_Executor_Model_Task $task */
        $task = $observer->getEvent()->getTask();
        /** @var Task_Log_Model_Task $log */
        $log = Mage::getModel('task_log/task');
        /** @var Mage_Core_Model_Date $date */
        $date = Mage::getModel('core/date');

        $log->load($task->getTaskId());
        if ($log->hasData()) {
            /** @var mixed[] $messages */
            $messages = $task->getStepWarnings();
            if (!empty($observer->getEvent()->getError())) {
                $messages[] = $observer->getEvent()->getError();
            }
            /** @var mixed[] $data */
            $data = [
                'task_id'    => $task->getTaskId(),
                'number'     => $task->getStepNumber(),
                'messages'   => $this->encodeMessages($messages),
                'created_at' => $date->gmtDate(),
            ];

            $log->addStep($data);

            $log->setStatus(Task_Log_Model_Task::TASK_STATUS_ERROR)->save();
        }

        return $this;
    }

    /**
     * Check messages and encode as JSON or return empty string
     *
     * @param mixed[] $messages
     *
     * @return string
     */
    private function encodeMessages(array $messages)
    {
        if (empty($messages)) {
            return '';
        }
        /** @var Mage_Core_Helper_Data $coreHelper */
        $coreHelper = Mage::helper('core');
        /** @var false|string $messages */
        $messages = $coreHelper->jsonEncode($messages);
        if (empty($messages)) {
            return '';
        }

        return $messages;
    }
}
