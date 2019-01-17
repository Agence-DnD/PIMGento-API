<?php

require_once 'abstract.php';

/**
 * Class Mage_Shell_Pimgento
 *
 * @category  Class
 * @package   Mage_Shell_Pimgento
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class Mage_Shell_Pimgento extends Mage_Shell_Abstract
{
    /**
     * Import argument string value
     *
     * @var string $importArgument
     */
    protected $importArgument = 'import';
    /**
     * Import types values
     *
     * @var string[] $importTypes
     */
    protected $importTypes = [
        'category',
        'family',
        'attribute',
        'option',
        'product_model',
        'family_variant',
        'product',
    ];
    /**
     * Types argument string value
     *
     * @var string $typeArgument
     */
    protected $typeArgument = 'types';

    /**
     * Run script
     *
     * @return void
     */
    public function run()
    {
        /** @var bool $typesArg */
        $typesArg = $this->getArg($this->typeArgument);
        if ($typesArg === true) {
            $this->displayAuthorizedTypes();

            return;
        }

        /** @var string $importArg */
        $importArg = $this->getArg($this->importArgument);
        if ($importArg === false || !is_string($importArg)) {
            $this->displayError('No argument value given.', true);

            return;
        }

        /** @var string[] $imports */
        $imports = explode(',', $importArg);
        /** @var string[] $unauthorized */
        $unauthorized = array_diff($imports, $this->getImportTypes());
        if (!empty($unauthorized)) {
            $unauthorized = implode(',', $unauthorized);
            $this->displayError(sprintf('Invalid import type given: "%s"', $unauthorized), true);

            return;
        }

        /** @var string $import */
        foreach ($imports as $import) {
            $this->runImport($import);
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php pimgento.php --[options]

  --import <type>           Run one import type or multiple coma separated ones 
  --types                   Display all authorized import types
  -h                        Short alias for help
  help                      This help

USAGE;
    }

    /**
     * Return authorized import types
     *
     * @return string[]
     */
    protected function getImportTypes()
    {
        return $this->importTypes;
    }

    /**
     * Display all authorized import types
     *
     * @return void
     */
    public function displayAuthorizedTypes()
    {
        /** @var string $imports */
        $imports = implode(', ', $this->getImportTypes());
        echo 'Authorized import types: ' . $imports . PHP_EOL;
        echo $this->usageHelp();
    }

    /**
     * Display given error message and help
     *
     * @param string $error
     * @param bool   $showHelp
     *
     * @return void
     */
    protected function displayError($error, $showHelp = false)
    {
        if (is_string($error)) {
            echo sprintf("\033[0;31mError: %s\033[0m%s", $error, PHP_EOL);
        }

        if ($showHelp) {
            echo $this->usageHelp();
        }
    }

    /**
     * Display import progression
     *
     * @param string[] $messages
     *
     * @return void
     */
    protected function displayProgress(array $messages)
    {
        if (!empty($messages['content']) && is_string($messages['content'])) {
            /** @var string $colorCode */
            $colorCode = '0;32';// Info and success
            if (!empty($messages['type']) && $messages['type'] !== 'success') {
                $colorCode = '0;33'; // Warning
            }
            echo sprintf("\033[%sm%s\033[0m%s", $colorCode, $messages['content'], PHP_EOL);
        }
    }

    /**
     * Display import warning messages
     *
     * @param mixed[] $warnings
     *
     * @return void
     */
    protected function displayWarnings($warnings)
    {
        if (empty($warnings)) {
            return;
        }
        if (!is_array($warnings)) {
            return;
        }

        /** @var string[] $warning */
        foreach ($warnings as $warning) {
            if (!is_array($warning)) {
                continue;
            }
            $this->displayProgress($warning);
        }
    }

    /**
     * Run given import
     *
     * @param string $import
     *
     * @return void
     */
    protected function runImport($import)
    {
        if (!is_string($import)) {
            $this->displayError('Trying to import with invalid code.');

            return;
        }

        //Enable error display
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        set_time_limit(0);
        umask(0);

        /** @var Task_Executor_Model_Task $executor */
        $executor = $this->_factory->getSingleton('task_executor/task');
        $executor->load($import);
        try {
            /** @var mixed[] $task */
            $task = $executor->getTask();

            /** @var string[] $separator */
            $separator = ['content' => '###'];
            $this->displayProgress($separator);
            $this->displayProgress(['content' => sprintf('Executing task to import %s', $import)]);
            $this->displayProgress($separator);

            /** @var null $totalSteps */
            $totalSteps = null;
            if (!empty($task['steps']) && is_array($task['steps'])) {
                $totalSteps = count($task['steps']);
            }
            if (is_null($totalSteps) || $totalSteps === 0) {
                $this->displayError(sprintf('No steps set for import %s', $import));

                return;
            }

            /** @var int $step */
            for ($step = 1; $step <= $totalSteps; $step++) {
                if ($executor->taskIsOver()) {
                    break;
                }
                $this->displayProgress($executor->getStepComment());
                $executor->execute();
                $this->displayWarnings($executor->getStepWarnings());
                $this->displayProgress($executor->getStepMessage());

                $executor->nextStep();
            }

            $this->displayProgress($separator);
            $this->displayProgress(['content' => sprintf('Task %s complete', $import)]);
        } catch (Task_Executor_Exception $exception) {
            $this->displayWarnings($executor->getStepWarnings());
            $this->displayError($exception->getMessage());
        }
    }
}

$shell = new Mage_Shell_Pimgento();
$shell->run();
