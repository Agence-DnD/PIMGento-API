<?php

/**
 * Class Task_Executor_Adminhtml_Task_ExecutorController
 *
 * @category  Class
 * @package   Task_Executor
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Executor_Adminhtml_Task_ExecutorController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Index Action
     *
     * @return void
     */
    public function indexAction()
    {
        $this->loadLayout();

        $this->_title($this->__('Tasks'))->_title($this->__('Executor'));

        $this->_setActiveMenu('task');

        $this->renderLayout();
    }

    /**
     * Options Action
     *
     * @return void
     * @throws Mage_Core_Exception
     */
    public function optionsAction()
    {
        /** @var string $command */
        $command = $this->getRequest()->getPost('command');
        /** @var Task_Executor_Model_Task $model */
        $model = Mage::getSingleton('task_executor/task');
        /** @var Task_Executor_Model_Task $task */
        $task = $model->load($command);

        Mage::register('task', $task);

        $this->loadLayout(false);
        $this->renderLayout();
    }

    /**
     * Upload File Action
     *
     * @return void
     */
    public function uploadAction()
    {
        if (empty($_FILES)) {
            return;
        }
        try {
            /** @var Varien_File_Uploader $uploader */
            $uploader = new Varien_File_Uploader("file");

            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);
            $uploader->setAllowCreateFolders(true);

            /** @var string $path */
            $path = $this->getUploadDir();

            /** @var Task_Executor_Helper_Data $helper */
            $helper = Mage::helper('task_executor');

            $uploader->setAllowedExtensions($helper->getAllowedExtensions());
            /** @var mixed[] $uploadSaveResult */
            $uploadSaveResult = $uploader->save($path, $_FILES['file']['name']);
            /** @var string[] $result */
            $result = [
                'error' => '',
                'file'  => $uploadSaveResult['file'],
            ];
        } catch (Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'file'  => '',
            ];
        }
        /** @var Mage_Core_Helper_Data $coreHelper */
        $coreHelper = Mage::helper('core');

        $this->getResponse()->setBody($coreHelper->jsonEncode($result));
    }

    /**
     * Launch task Action
     *
     * @return void
     * @throws Exception
     */
    public function launchAction()
    {
        /** @var int $step */
        $step = (int)$this->getRequest()->getPost('step');
        /** @var string $command */
        $command = $this->getRequest()->getPost('command', 'none');
        /** @var string $taskId */
        $taskId = $this->getRequest()->getPost('task_id');
        /** @var mixed[] $options */
        $options = $this->getRequest()->getPost('options', []);
        /** @var mixed[] $messages */
        $messages = [];

        /** @var Mage_Core_Helper_Data $helper */
        $helper = Mage::helper('core');

        /** @var Task_Executor_Model_Task $model */
        $model = Mage::getSingleton('task_executor/task');
        /** @var Task_Executor_Model_Task $task */
        $task = $model->load($command);

        try {
            $task->setStepNumber($step);

            if (!empty($options)) {
                $task->addOptions($helper->jsonDecode($options));
            }

            if (!empty($taskId)) {
                $task->setTaskId($taskId);
            }

            if ($step === 0) {
                $messages[] = $task->getStepComment();
            }

            $task->execute();

            /** @var mixed[] $warnings */
            $warnings   = $task->getStepWarnings();
            $messages   = array_merge($messages, $warnings);
            $messages[] = $task->getStepMessage();

            $task->nextStep();
            $messages[] = $task->getStepComment();

            /** @var mixed[] $result */
            $result = [
                'messages' => $messages,
                'launch'   => $task->getStepNumber(),
                'task_id'  => $task->getTaskId(),
                'options'  => $helper->jsonEncode($task->getOptions()->toArray()),
            ];
        } catch (Exception $exception) {
            /** @var mixed[] $warnings */
            $warnings   = $task->getStepWarnings();
            $messages   = array_merge($messages, $warnings);
            $messages[] = [
                'type'    => 'error',
                'content' => sprintf('%s Error: %s', $task->getStepCommentPrefix(), $exception->getMessage()),
            ];

            /** @var mixed[] $result */
            $result = ['messages' => $messages, 'launch' => false];
        }

        $this->getResponse()->setBody($helper->jsonEncode($result));
    }

    /**
     * Prepare warnings to be processed by frontend JS
     *
     * @param mixed[] $warnings
     *
     * @return mixed[]
     */
    protected function formatStepWarnings(array $warnings)
    {
        /** @var mixed[] $formatted */
        $formatted = [];
        if (empty($warnings)) {
            return $formatted;
        }
        if (is_string($warnings)) {
            $warnings = [$warnings];
        }
        if (!is_array($warnings)) {
            return $formatted;
        }

        /** @var string $warning */
        foreach ($warnings as $warning) {
            if (!is_string($warning)) {
                continue;
            }
            $formatted[] = ['type' => 'emph', 'content' => $warning];
        }
        return $formatted;
    }

    /**
     * Retrieve Upload Directory
     *
     * @return string
     */
    protected function getUploadDir()
    {
        /** @var Task_Executor_Helper_Data $helper */
        $helper = Mage::helper('task_executor');

        return $helper->getUploadDir();
    }
}
