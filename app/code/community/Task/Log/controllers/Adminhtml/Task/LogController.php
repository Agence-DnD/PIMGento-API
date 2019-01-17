<?php

/**
 * Class Task_Log_Adminhtml_Task_LogController
 *
 * @category  Class
 * @package   Task_Log
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Log_Adminhtml_Task_LogController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Index Action
     */
    public function indexAction()
    {
        $this->_initAction()->renderLayout();
    }

    /**
     * View Action
     */
    public function viewAction()
    {
        /** @var string $taskId */
        $taskId = $this->getRequest()->getParam('id');

        if ($taskId) {
            /** @var Task_Log_Model_Task $model */
            $model = Mage::getModel('task_log/task');
            $model->load($taskId);

            if ($model->hasData()) {
                Mage::register('task', $model);
            }
        }

        if (!Mage::registry('task')) {
            $this->_redirect('*/*/index');
        } else {
            $this->_initAction()->renderLayout();
        }
    }

    /**
     * Initialize action
     *
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function _initAction()
    {
        $this->loadLayout();

        $this->_title($this->__('Tasks'))
            ->_title($this->__('Log'));

        $this->_setActiveMenu('task');

        return $this;
    }

    /**
     * Task Grid Action (Ajax)
     */
    public function taskGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('task_log/adminhtml_task_log_grid')->toHtml()
        );
    }

    /**
     * Mass Delete Action
     */
    public function massDeleteAction()
    {
        $ids = $this->getRequest()->getParam('task_id');
        if (!is_array($ids)) {
            $this->_getSession()->addError($this->__('Please select task(s).'));
        } else {
            if (!empty($ids)) {
                try {
                    foreach ($ids as $id) {
                        $task = Mage::getSingleton('task_log/task')->load($id);
                        $task->delete();
                    }
                    $this->_getSession()->addSuccess(
                        $this->__('Total of %d record(s) have been deleted.', count($ids))
                    );
                } catch (Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                }
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Export order grid to CSV format
     */
    public function exportCsvAction()
    {
        /** @var Task_Log_Block_Adminhtml_Task_Log_Grid $grid */
        $grid = $this->getLayout()->createBlock('task_log/adminhtml_task_log_grid');
        $this->_prepareDownloadResponse('tasks.csv', $grid->getCsvFile());
    }

    /**
     * Export order grid to XML format
     */
    public function exportXmlAction()
    {
        /** @var Task_Log_Block_Adminhtml_Task_Log_Grid $grid */
        $grid = $this->getLayout()->createBlock('task_log/adminhtml_task_log_grid');
        $this->_prepareDownloadResponse('tasks.xml', $grid->getXml());
    }

    /**
     * Export order grid to XML Excel format
     */
    public function exportExcelAction()
    {
        /** @var Task_Log_Block_Adminhtml_Task_Log_Grid $grid */
        $grid = $this->getLayout()->createBlock('task_log/adminhtml_task_log_grid');
        $this->_prepareDownloadResponse('tasks.xml', $grid->getExcelFile());
    }
}
