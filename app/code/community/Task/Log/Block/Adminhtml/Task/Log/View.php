<?php

/**
 * Class Task_Log_Block_Adminhtml_Task_Log_View
 *
 * @category  Class
 * @package   Task_Log
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Log_Block_Adminhtml_Task_Log_View extends Mage_Adminhtml_Block_Template
{
    /**
     * Preparing global layout
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        /** @var Mage_Adminhtml_Block_Widget_Button $block */
        $block = $this->getLayout()->createBlock('adminhtml/widget_button')->setData(
            [
                'label'   => Mage::helper('task_log')->__('Back'),
                'onclick' => 'setLocation(\'' . $this->getUrl('*/*/') . '\')',
                'class'   => 'back',
            ]
        );

        $this->setChild('back_button', $block);

        return parent::_prepareLayout();
    }

    /**
     * Retrieve back button html code
     *
     * @return string
     */
    public function getBackButtonHtml()
    {
        return $this->getChildHtml('back_button');
    }

    /**
     * Get page header text
     *
     * @return string
     */
    public function getHeader()
    {
        return Mage::helper('task_log')->__('Task');
    }

    /**
     * Retrieve task
     *
     * @return Task_Log_Model_Task
     */
    public function getTask()
    {
        return Mage::registry('task');
    }

    /**
     * Retrieve steps
     *
     * @return mixed[]
     */
    public function getSteps()
    {
        return $this->getTask()->getSteps();
    }

    /**
     * Render all steps messages as list elements
     *
     * @return string
     */
    public function renderStepsMessages()
    {
        /** @var string $html */
        $html = '';
        /** @var mixed[] $steps */
        $steps = $this->getSteps();
        if (empty($steps)) {
            return $html;
        }

        /** @var Mage_Core_Helper_Data $coreHelper */
        $coreHelper = Mage::helper('core');
        /** @var mixed[] $step */
        foreach ($steps as $step) {
            if (empty($step['messages'])) {
                continue;
            }

            /** @var mixed[] $messages */
            $messages = $coreHelper->jsonDecode($step['messages']);
            if (!is_array($messages)) {
                continue;
            }
            /** @var string[] $message */
            foreach ($messages as $message) {
                if (empty($message['content'])) {
                    continue;
                }
                /** @var string $classes */
                $classes = 'step-line';
                if (!empty($message['type'])) {
                    $classes .= sprintf(' %s', $message['type']);
                }

                $html .= sprintf('<li class="%s">%s</li>', $classes, $message['content']);
            }
        }

        return $html;
    }
}
