<?php

/**
 * Class Task_Log_Block_Adminhtml_Task_Log
 *
 * @category  Class
 * @package   Task_Log
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Log_Block_Adminhtml_Task_Log extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_controller = 'adminhtml_task_log';
        $this->_blockGroup = 'task_log';
        $this->_headerText = Mage::helper('task_log')->__('Task Logs');

        parent::__construct();

        $this->removeButton('add');
    }
}
