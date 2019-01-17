<?php

/**
 * Class Task_Executor_Model_Cron
 *
 * @category  Class
 * @package   Task_Executor
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Executor_Model_Cron
{

    /**
     * Execute task
     *
     * @param string $command
     * @param array $options
     *
     * @return Task_Executor_Model_Cron
     */
    public function launch($command, $options = null)
    {
        /** @var Task_Executor_Model_Task $model */
        $model = Mage::getSingleton('task_executor/task');
        /** @var Task_Executor_Model_Task $task */
        $task = $model->load($command);

        if ($options) {
            $task->addOptions($options);
        }
        $task->executeAll();

        return $this;
    }

}