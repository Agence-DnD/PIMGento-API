<?php

/**
 * Class Task_Executor_Block_Adminhtml_Task_View_Options
 *
 * @category  Class
 * @package   Task_Executor
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Executor_Block_Adminhtml_Task_View_Options extends Mage_Adminhtml_Block_Template
{

    /**
     * Retrieve task value with key
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getValue($key)
    {
        /** @var Task_Executor_Model_Task $model */
        $model = Mage::registry('task');

        $task = $model->getTask();

        if (isset($task[$key])) {
            return $task[$key];
        }

        return false;
    }

    /**
     * Retrieve renderer for option
     *
     * @param array $option
     *
     * @return string
     */
    public function getRenderer($option)
    {
        $renderer = 'task_executor/adminhtml_task_view_options_view';

        if (isset($option['renderer'])) {
            $renderer = $option['renderer'];
        }

        /** @var Mage_Core_Block_Abstract $block */
        $block = $this->getLayout()->createBlock($renderer);

        $block->setData($option);

        if (isset($option['template'])) {
            $block->setTemplate($option['template']);
        }

        return $block->toHtml();
    }
}
