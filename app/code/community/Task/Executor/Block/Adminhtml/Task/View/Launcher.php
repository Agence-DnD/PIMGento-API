<?php

/**
 * Class Task_Executor_Block_Adminhtml_Task_View_Launcher
 *
 * @category  Class
 * @package   Task_Executor
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Executor_Block_Adminhtml_Task_View_Launcher extends Mage_Uploader_Block_Multiple
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setTemplate('task/executor/view/launcher.phtml');

        $type = $this->_getMediaType();

        /** @var Task_Executor_Helper_Data $helper */
        $helper = Mage::helper('task_executor');

        $allowed = $helper->getAllowedExtensions();

        $labels = [];
        $files  = [];

        foreach ($allowed as $ext) {
            $labels[] = '.' . $ext;
            $files[]  = '*.' . $ext;
        }

        /** @var Mage_Adminhtml_Model_Url $urlModel */
        $urlModel = Mage::getModel('adminhtml/url');

        $this->getUploaderConfig()
            ->setFileParameterName('file')
            ->setTarget(
                $urlModel->addSessionParam()->getUrl('adminhtml/task_executor/upload', ['type' => $type])
            );

        $this->getButtonConfig()
            ->setAttributes(
                [
                    'accept' => $allowed
                ]
            );
    }

    /**
     * Prepare Layout
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        /** @var Mage_Core_Block_Abstract $block */
        $block = $this->getLayout()->createBlock('adminhtml/widget_button')->setData([
                    'label'   => Mage::helper('task_executor')->__('Execute'),
                    'onclick' => 'taskExecutor.run(this.rel, this.id);',
                    'class'   => 'save',
                ]);

        $this->setChild('execute_button', $block);

        return parent::_prepareLayout();
    }

    /**
     * Return current media type based on request or data
     *
     * @return string
     * @throws Exception
     */
    protected function _getMediaType()
    {
        if ($this->hasData('media_type')) {
            return $this->_getData('media_type');
        }
        return $this->getRequest()->getParam('type');
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

    /**
     * Get html code of button
     *
     * @return string
     */
    public function getExecuteButtonHtml()
    {
        return $this->getChildHtml('execute_button');
    }

    /**
     * Retrieve All Tasks
     *
     * @return array
     */
    public function getTasks()
    {
        /** @var Task_Executor_Helper_Data $helper */
        $helper = Mage::helper('task_executor');

        return $helper->getTasks();
    }

    /**
     * Retrieve Task As Json Object
     *
     * @return string
     */
    public function getTasksJson()
    {
        $tasks = $this->getTasks();

        /** @var Mage_Core_Helper_Data $helper */
        $helper = Mage::helper('core');

        return $helper->jsonEncode($tasks);
    }
}
