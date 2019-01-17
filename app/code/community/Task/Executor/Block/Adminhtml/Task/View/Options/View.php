<?php

/**
 * Class Task_Executor_Block_Adminhtml_Task_View_Options_View
 *
 * @category  Class
 * @package   Task_Executor
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Executor_Block_Adminhtml_Task_View_Options_View extends Mage_Adminhtml_Block_Template
{

    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function getTemplate()
    {
        if (!$this->_template) {
            $this->_template = 'task/executor/view/options/' . $this->getType() . '.phtml';
        }

        return $this->_template;
    }
}
