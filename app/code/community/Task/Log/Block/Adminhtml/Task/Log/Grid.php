<?php

/**
 * Class Task_Log_Block_Adminhtml_Task_Log_Grid
 *
 * @category  Class
 * @package   Task_Log
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Task_Log_Block_Adminhtml_Task_Log_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setDefaultSort('created_at');
        $this->setDefaultDir('desc');
        $this->setId('task_grid');
        $this->setSaveParametersInSession(true);

        $this->setUseAjax(true);
    }

    /**
     * Prepare Collection
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        /** @var Task_Log_Model_Resource_Task_Collection $collection */
        $collection = Mage::getModel('task_log/task')->getCollection();

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare Columns
     *
     * @return Task_Log_Block_Adminhtml_Task_Log_Grid
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'task_id',
            [
                'header'  => Mage::helper('task_log')->__('Id'),
                'align'   => 'left',
                'index'   => 'task_id',
                'width'   => '120px',
            ]
        );

        $this->addColumn(
            'command',
            [
                'header'  => Mage::helper('task_log')->__('Command'),
                'align'   => 'left',
                'index'   => 'command',
            ]
        );

        $this->addColumn(
            'task_label',
            [
                'header'  => Mage::helper('task_log')->__('Task'),
                'align'   => 'left',
                'index'   => 'task_label',
            ]
        );

        $this->addColumn(
            'user',
            [
                'header'  => Mage::helper('task_log')->__('User'),
                'align'   => 'left',
                'index'   => 'user',
            ]
        );

        $options = [
            Task_Log_Model_Task::TASK_STATUS_SUCCESS    => Mage::helper('task_log')->__('Success'),
            Task_Log_Model_Task::TASK_STATUS_PROCESSING => Mage::helper('task_log')->__('Processing'),
            Task_Log_Model_Task::TASK_STATUS_ERROR      => Mage::helper('task_log')->__('Error'),
        ];

        $this->addColumn(
            'status',
            [
                'header'         => Mage::helper('task_log')->__('Status'),
                'align'          => 'left',
                'index'          => 'status',
                'width'          => '150px',
                'frame_callback' => [$this, 'decorateStatus'],
                'type'           => 'options',
                'options'        => $options,
            ]
        );

        $this->addColumn(
            'created_at',
            [
                'header'  => Mage::helper('task_log')->__('Created At'),
                'align'   => 'left',
                'type'    => 'datetime',
                'index'   => 'created_at',
            ]
        );

        $this->addColumn(
            'action',
            [
                'header'    =>  Mage::helper('task_log')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => [
                    [
                        'caption' => Mage::helper('task_log')->__('View'),
                        'url'     => ['base'=> '*/*/view'],
                        'field'   => 'id'
                    ],
                ],
                'filter'    => false,
                'sortable'  => false,
                'is_system' => true,
            ]
        );

        $this->addExportType('*/*/exportCsv', Mage::helper('task_log')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('task_log')->__('XML'));
        $this->addExportType('*/*/exportExcel', Mage::helper('task_log')->__('XML Excel'));

        return parent::_prepareColumns();
    }

    /**
     * Decorate status column values
     *
     * @param string $value
     * @param Mage_Index_Model_Process $row
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @param bool $isExport
     *
     * @return string
     */
    public function decorateStatus($value, $row, $column, $isExport)
    {
        $print = $value;

        if (!$isExport) {
            $class = '';
            switch ($row->getStatus()) {
                case Task_Log_Model_Task::TASK_STATUS_SUCCESS :
                    $class = 'grid-severity-notice';
                    break;
                case Task_Log_Model_Task::TASK_STATUS_PROCESSING :
                    $class = 'grid-severity-major';
                    break;
                case Task_Log_Model_Task::TASK_STATUS_ERROR :
                    $class = 'grid-severity-critical';
                    break;
            }

            $print = '<span class="' . $class . '"><span>' . $value . '</span></span>';
        }

        return $print;
    }

    /**
     * Retrieve Row Url
     *
     * @param object $row
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/view', ['id' => $row->getId()]);
    }

    /**
     * Retrieve Grid Url (Ajax)
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/taskGrid', ['_current' => true]);
    }

    /**
     * Prepare Massaction
     *
     * @return Task_Log_Block_Adminhtml_Task_Log_Grid
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('task_id');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label'   => Mage::helper('task_log')->__('Delete'),
                'url'     => $this->getUrl('*/*/massDelete'),
                'confirm' => Mage::helper('task_log')->__('Are you sure?')
            ]
        );

        return $this;
    }
}
