<?php

/**
 * Class Task_Executor_Model_Task
 *
 * @category  Class
 * @package   Task_Executor
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Executor_Model_Task extends Varien_Object
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->loadAllTasks();
    }

    /**
     * Load task
     *
     * @param string $command
     *
     * @return Task_Executor_Model_Task
     */
    public function load($command)
    {
        /** @var mixed[] $tasks */
        $tasks = $this->getTasks();

        $this->setTaskId($this->getUniqueId());
        $this->printPrefix(true);

        if (empty($tasks[$command])) {
            $this->addTask($command, []);
            /** @var mixed[] $tasks */
            $tasks = $this->getTasks();
        }

        $this->setData('command', $command);
        $this->setData('task', $tasks[$command]);
        $this->setStepNumber(0);
        $this->setStepCount(count($tasks[$command]['steps']));

        return $this;
    }

    /**
     * Execute task
     *
     * @return void
     * @throws Task_Executor_Exception
     */
    public function execute()
    {
        $this->beforeExecute();

        try {
            /** @var string $method */
            $method = $this->getStepMethod();
            /** @var string $command */
            $command = $this->getData('command');
            if (empty($method) || empty($command)) {
                $this->stop($this->getHelper()->__('Invalid command or method for current task. Please check your task configuration.'));
            }
            Mage::dispatchEvent('task_executor_step_start', ['task' => $this]);
            /** @var string $customStartEvent */
            $customStartEvent = sprintf('task_executor_step_start_%s', strtolower($command));
            Mage::dispatchEvent($customStartEvent, ['task' => $this]);

            /** @var int|false $syntaxCheck */
            $syntaxCheck = preg_match('#[a-zA-Z_\/]+::[a-zA-Z_]+#', $method);
            if ($syntaxCheck !== 1) {
                $this->stop($this->getHelper()->__('Wrong syntax for declared method: %s', $method));
            }

            /**
             * @var string $alias
             * @var string $method
             */
            list($alias, $method) = explode('::', $method);

            /** @var object $model */
            $model = Mage::getSingleton($alias);
            if (empty($model)) {
                $this->stop($this->getHelper()->__('"%s" model does not exists', $alias));
            }
            if (!method_exists($model, $method)) {
                $this->stop($this->getHelper()->__('"%s" method does not exists in "%s"', $method, get_class($model)));
            }
            $model->$method($this);

            Mage::dispatchEvent('task_executor_step_end', ['task' => $this]);
            /** @var string $customEndEvent */
            $customEndEvent = sprintf('task_executor_step_end_%s', strtolower($command));
            Mage::dispatchEvent($customEndEvent, ['task' => $this]);
        } catch (Exception $exception) {
            $this->dispatchError($exception->getMessage());
            throw new Task_Executor_Exception($exception->getMessage());
        }

        $this->afterExecute();
    }

    /**
     * Execute all steps
     *
     * @return Task_Executor_Model_Task
     */
    public function executeAll()
    {
        try {
            while (!$this->taskIsOver()) {
                $this->execute();
                $this->nextStep();
            }
        } catch (Exception $e) {
            $this->dispatchError($e->getMessage());
        }

        return $this;
    }

    /**
     * Switch to the next Step
     *
     * @return Task_Executor_Model_Task
     */
    public function nextStep()
    {
        if (!$this->getLock()) {
            /** @var int $stepNumber */
            $stepNumber = (int)$this->getStepNumber();
            $this->setStepNumber($stepNumber + 1);
        }

        return $this;
    }

    /**
     * Switch to the previous Step
     *
     * @return Task_Executor_Model_Task
     */
    public function previousStep()
    {
        if (!$this->getLock()) {
            /** @var int $stepNumber */
            $stepNumber = (int)$this->getStepNumber();
            $this->setStepNumber($stepNumber - 1);
        }

        return $this;
    }

    /**
     * Prevented from proceeding to the next or previous step
     *
     * @return Task_Executor_Model_Task
     */
    public function lockStep()
    {
        $this->setLock(true);

        return $this;
    }

    /**
     * Unlock next and previous movements
     *
     * @return Task_Executor_Model_Task
     */
    public function unlockStep()
    {
        $this->unsLock();

        return $this;
    }

    /**
     * Set step number and set associated step
     *
     * @param int $number
     *
     * @return Task_Executor_Model_Task
     */
    public function setStepNumber($number)
    {
        /** @var mixed[] $task */
        $task = $this->getTask();

        $this->unsetData('step_number');

        if (!empty($task) && !empty($task['steps'][$number])) {
            $this->setData('step_number', $number);
        }

        return $this;
    }

    /**
     * Retrieve current step
     *
     * @return string[]
     */
    public function getCurrentStep()
    {
        /** @var mixed[] $task */
        $task = $this->getTask();
        /** @var int $stepNumber */
        $stepNumber = $this->getStepNumber();
        /** @var string[] $currentStep */
        $currentStep = [];
        if (isset($stepNumber) && !empty($task['steps'][$stepNumber])) {
            $currentStep = $task['steps'][$stepNumber];
        }

        return $currentStep;
    }

    /**
     * Retrieve current step method. Send false if step is undefined
     *
     * @return string
     */
    public function getStepMethod()
    {
        /** @var string[] $step */
        $step = $this->getCurrentStep();
        /** @var string $method */
        $method = '';
        if (!empty($step) && !empty($step['method'])) {
            $method = $step['method'];
        }

        return $method;
    }

    /**
     * Retrieve current step comment. Send false if step is undefined
     *
     * @return string[]
     */
    public function getStepComment()
    {
        /** @var string[] $comment */
        $comment = [];
        /** @var string[] $step */
        $step = $this->getCurrentStep();
        if (!empty($step) && !empty($step['comment'])) {
            /** @var string[] $comment */
            $comment = [
                'type'    => 'success',
                'content' => $this->getStepCommentPrefix() . $step['comment'],
            ];
        }

        return $comment;
    }

    /**
     * Set end step message
     *
     * @param string $message
     *
     * @return Task_Executor_Model_Task
     */
    public function setStepMessage($message)
    {
        /** @var int $stepNumber */
        $stepNumber = $this->getStepNumber();
        /** @var string $messageKey */
        $messageKey = sprintf('message_step_%s', $stepNumber);
        /** @var string[] $stepMessage */
        $stepMessage = [
            'type'    => 'success',
            'content' => $this->getStepCommentPrefix() . $message,
        ];
        $this->setData($messageKey, $stepMessage);

        return $this;
    }

    /**
     * Retrieve end step message
     *
     * @return string[]
     */
    public function getStepMessage()
    {
        /** @var int $stepNumber */
        $stepNumber = $this->getStepNumber();
        /** @var string $messageKey */
        $messageKey = sprintf('message_step_%s', $stepNumber);
        /** @var string[] $stepMessage */
        $stepMessage = [
            'type'    => 'success',
            'content' => $this->getStepCommentPrefix() . $this->getHelper()->__('Step completed'),
        ];
        if ($this->hasData($messageKey)) {
            $stepMessage = $this->getData($messageKey);
        }

        return $stepMessage;
    }

    /**
     * Add new current step warning message
     *
     * @param string $message
     *
     * @return void
     */
    public function setStepWarning($message)
    {
        if (!is_string($message)) {
            return;
        }
        /** @var string $stepKey */
        $stepKey = sprintf('warning_step_%s', $this->getStepNumber());
        /** @var mixed[] $warnings */
        $warnings   = $this->getStepWarnings();
        $warnings[] = [
            'type'    => 'emph',
            'content' => sprintf('%sWarning: %s', $this->getStepCommentPrefix(), $message),
        ];
        $this->setData($stepKey, $warnings);
    }

    /**
     * Get all current step warning messages
     *
     * @return mixed[]
     */
    public function getStepWarnings()
    {
        /** @var string $stepKey */
        $stepKey = sprintf('warning_step_%s', $this->getStepNumber());
        /** @var string[] $warnings */
        $warnings = [];
        if (!empty($this->getData($stepKey))) {
            $warnings = $this->getData($stepKey);
        }

        return $warnings;
    }

    /**
     * Retrieve task options
     *
     * @return Varien_Object
     */
    public function getOptions()
    {
        if (!$this->hasData('options')) {
            $this->setData('options', new Varien_Object());
        }

        return $this->getData('options');
    }

    /**
     * Transform options to object
     *
     * @param mixed[] $items
     *
     * @return Task_Executor_Model_Task
     */
    public function addOptions(array $items)
    {
        foreach ($items as $key => $value) {
            if (!is_object($value)) {
                $this->getOptions()->setData($key, $value);
            }
        }

        return $this;
    }

    /**
     * Add Task to executor
     *
     * @param string  $command
     * @param mixed[] $data
     *
     * @return Task_Executor_Model_Task
     */
    public function addTask($command, $data)
    {
        /** @var mixed[] $tasks */
        $tasks = [];

        if ($this->hasData('tasks')) {
            $tasks = $this->getData('tasks');
        }

        $data['steps'][0]                     = [
            'comment' => $this->getHelper()->__('Start task'),
            'method'  => 'task_executor/task::startTask',
        ];
        $data['steps'][count($data['steps'])] = [
            'comment' => $this->getHelper()->__('End task'),
            'method'  => 'task_executor/task::endTask',
        ];

        ksort($data['steps']);
        $tasks[$command] = $data;

        $this->setData('tasks', $tasks);

        return $this;
    }

    /**
     * Check task is over
     *
     * @return bool
     */
    public function taskIsOver()
    {
        return is_null($this->getStepNumber());
    }

    /**
     * Start Task
     *
     * @return bool
     */
    public function startTask()
    {
        /** @var string $message */
        $message = [$this->getHelper()->__('Task id: %s', $this->getTaskId())];
        /** @var mixed[] $options */
        $options = $this->getOptions()->toArray();
        if (!empty($options)) {
            /** @var Mage_Core_Helper_Data $coreHelper */
            $coreHelper = Mage::helper('core');
            $message[]  = $coreHelper->jsonEncode($options);
        }

        /** @var string $stepMessage */
        $stepMessage = implode(' ', $message);
        $this->setStepMessage($stepMessage);

        return true;
    }

    /**
     * End Task
     *
     * @return bool
     */
    public function endTask()
    {
        $this->setStepMessage($this->getHelper()->__('Task id: %s', $this->getTaskId()));

        return true;
    }

    /**
     * Retrieve all tasks with event
     *
     * @return void
     */
    public function loadAllTasks()
    {
        Mage::dispatchEvent('task_executor_load_task_before', ['task' => $this]);

        if (!$this->getData('tasks')) {
            Mage::dispatchEvent('task_executor_load_task', ['task' => $this]);
        }

        Mage::dispatchEvent('task_executor_load_task_after', ['task' => $this]);
    }

    /**
     * Add prefix to messages
     *
     * @param bool $state
     *
     * @return Task_Executor_Model_Task
     */
    public function printPrefix($state)
    {
        $this->setData('print_prefix', $state);

        return $this;
    }

    /**
     * Retrieve step comment prefix
     *
     * @return string
     */
    public function getStepCommentPrefix()
    {
        $prefix = '';
        if ($this->getData('print_prefix')) {
            $prefix = sprintf('[%s] ', $this->getTime());
        }

        return $prefix;
    }

    /**
     * Dispatch event error
     *
     * @param string $message
     *
     * @return Task_Executor_Model_Task
     */
    public function dispatchError($message)
    {
        /** @var string[] $error */
        $error = [
            'type'    => 'error',
            'content' => sprintf('%s Error: %s', $this->getStepCommentPrefix(), $message),
        ];
        Mage::dispatchEvent('task_executor_error', ['error' => $error, 'task' => $this]);

        return $this;
    }

    /**
     * Stop the import (no step will be processed after)
     *
     * @param $error
     *
     * @return void
     * @throws Task_Executor_Exception
     */
    public function stop($error)
    {
        throw new Task_Executor_Exception($error);
    }

    /**
     * Retrieve all commands
     *
     * @return mixed[]
     */
    public function getCommands()
    {
        /** @var mixed[] $task */
        $task = $this->getTasks();
        /** @var mixed[] $commands */
        $commands = [];
        if (!empty($task)) {
            $commands = array_keys($task);
        }

        return $commands;
    }

    /**
     * Before execute step
     *
     * @return void
     */
    protected function beforeExecute()
    {
        if ($this->getStepNumber() !== 0) {
            return;
        }
        Mage::dispatchEvent('task_executor_start', ['task' => $this]);
        /** @var string $customEvent */
        $customEvent = sprintf('task_executor_start_%s', strtolower($this->getData('command')));
        Mage::dispatchEvent($customEvent, ['task' => $this]);
    }

    /**
     * After execute step
     *
     * @return void
     */
    protected function afterExecute()
    {
        if ($this->getStepNumber() !== $this->getStepCount() - 1) {
            return;
        }
        Mage::dispatchEvent('task_executor_end', ['task' => $this]);
        /** @var string $customEvent */
        $customEvent = sprintf('task_executor_end_%s', strtolower($this->getData('command')));
        Mage::dispatchEvent($customEvent, ['task' => $this]);
    }

    /**
     * Retrieve default id
     *
     * @return string
     */
    protected function getUniqueId()
    {
        return uniqid();
    }

    /**
     * Retrieve Current Time
     *
     * @return string
     */
    protected function getTime()
    {
        /** @var Mage_Core_Model_Date $dateModel */
        $dateModel = Mage::getModel('core/date');

        return $dateModel->date('H:i:s');
    }

    /**
     * Retrieve TaskExecutor Helper
     *
     * @return Task_Executor_Helper_Data
     */
    protected function getHelper()
    {
        return Mage::helper('task_executor');
    }
}
