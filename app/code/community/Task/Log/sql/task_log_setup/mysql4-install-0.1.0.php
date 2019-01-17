<?php

/**
 * @category  Setup
 * @package   Task_Log
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

$installer->run(
    "

DROP TABLE IF EXISTS `{$this->getTable('task_log/task')}`;
CREATE TABLE `{$installer->getTable('task_log/task')}` (
    `task_id`    VARCHAR(255) NOT NULL DEFAULT '',
    `command`    VARCHAR(255) NULL DEFAULT '',
    `task_label` VARCHAR(255) NULL DEFAULT '',
    `status`     INT(1) NULL DEFAULT 1,
    `options`    TEXT NULL,
    `user`       VARCHAR(255) NULL DEFAULT 'System',
    `step_count` INT(11) NULL DEFAULT 0,
    `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Task Log';

DROP TABLE IF EXISTS `{$this->getTable('task_log/step')}`;
CREATE TABLE `{$this->getTable('task_log/step')}` (
    `task_id`    VARCHAR(255) NOT NULL DEFAULT '',
    `number`     INT(11) NULL DEFAULT 0,
    `messages`   TEXT NULL DEFAULT '',
    `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `FK_TASK_LOG_TASK_ID` FOREIGN KEY (`task_id`)
    REFERENCES `{$this->getTable('task_log/task')}` (`task_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Step Log';

"
);

$installer->endSetup();
