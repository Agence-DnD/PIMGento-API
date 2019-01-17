<?php

/**
 * Class Task_Executor_Block_Adminhtml_Task_View
 *
 * @category  Class
 * @package   Task_Executor
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Executor_Block_Adminhtml_Task_View extends Mage_Adminhtml_Block_Template
{

    /**
     * Prepare Layout
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        $launcher = $this->getLayout()->createBlock('task_executor/adminhtml_task_view_launcher');

        $this->setChild('launcher', $launcher);

        return parent::_prepareLayout();
    }

    /**
     * Get html of uploader
     *
     * @return string
     */
    public function getLauncher()
    {
        return $this->getChildHtml('launcher');
    }

    /**
     * Get page header text
     *
     * @return string
     */
    public function getHeader()
    {
        return Mage::helper('task_executor')->__('Tasks');
    }
}
