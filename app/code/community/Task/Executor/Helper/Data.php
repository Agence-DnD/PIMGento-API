<?php

/**
 * Class Task_Executor_Helper_Data
 *
 * @category  Class
 * @package   Task_Executor
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Executor_Helper_Data extends Mage_Core_Helper_Data
{

    /**
     * Retrieve Task
     *
     * @return array
     */
    public function getTasks()
    {
        /** @var Task_Executor_Model_Task $task */
        $task = Mage::getSingleton('task_executor/task');

        return $task->getTasks();
    }

    /**
     * Retrieve allowed extensions for uploader
     *
     * @return array
     */
    public function getAllowedExtensions()
    {
        return ['csv', 'txt'];
    }

    /**
     * Retrieve File Upload Directory
     *
     * @return string
     */
    public function getUploadDir()
    {
        $directory = Mage::getBaseDir('var') . DS . 'task' . DS . 'upload' . DS;

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        return $directory;
    }

    /**
     * Retrieve Cron File Directory
     *
     * @return string
     */
    public function getCronDir()
    {
        $directory = Mage::getBaseDir('var') . DS . 'task' . DS . 'cron' . DS;

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        return $directory;
    }
}
