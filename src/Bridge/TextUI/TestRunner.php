<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PHPUnit\Bridge\TextUI;

use PhpGuard\Application\Bridge\CodeCoverageRunner;
use PhpGuard\Application\Container;
use PhpGuard\Application\Event\ResultEvent;
use PhpGuard\Plugins\PHPUnit\Inspector;
use PhpGuard\Plugins\PHPUnit\Bridge\TestListener;
use PhpGuard\Application\Util\Filesystem;

use PHP_CodeCoverage_Filter;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;
use PHPUnit_Runner_TestSuiteLoader;
use PHPUnit_TextUI_TestRunner;

/**
 * Class TestRunner
 *
 */
class TestRunner extends PHPUnit_TextUI_TestRunner
{
    /**
     * @var TestListener
     */
    private $testListener;

    private $testFiles = array();

    /**
     * @var CodeCoverageRunner
     */
    private $coverageRunner;

    public function __construct(PHPUnit_Runner_TestSuiteLoader $loader = null, PHP_CodeCoverage_Filter $filter = null)
    {
        $this->coverageRunner = $coverageRunner = CodeCoverageRunner::getCached();
        $filter = $coverageRunner->getFilter();

        parent::__construct($loader, $filter);
        if(is_file($file=Inspector::getResultFileName())){
            unlink($file);
        }
        $this->testListener = new TestListener();
        $this->testListener->setCoverage($coverageRunner);
        $this->configureErrorHandler();
    }

    public function doRun(PHPUnit_Framework_Test $suite, array $arguments = array())
    {

        $arguments['listeners'][] = $this->testListener;
        $result = parent::doRun($suite,$arguments);
        $results = $this->testListener->getResults();
        Filesystem::serialize(Inspector::getResultFileName(),$results);
        $this->coverageRunner->saveState();
        return $result;
    }

    public function getTest($suiteClassName, $suiteClassFile = '', $suffixes = '')
    {
        // check if suite has comma separate forms
        if(false===strpos($suiteClassName,',')){
            return parent::getTest($suiteClassName, $suiteClassFile, $suffixes);
        }
        $files = explode(',',$suiteClassName);
        $files = array_unique($files);
        $suite = new PHPUnit_Framework_TestSuite('PhpGuard Unit Tests');
        $suite->addTestFiles($files);
        return $suite;
    }

    private function configureErrorHandler()
    {
        @unlink($file=sys_get_temp_dir().'/phpspec_error.log');
        ini_set('display_errors', 0);
        ini_set('error_log',$file);
        register_shutdown_function(array($this,'handleShutdown'));
    }

    public function handleShutdown()
    {
        $fatalErrors = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
        $lastError = error_get_last();

        if($lastError && in_array($lastError['type'],$fatalErrors)){
            $message = 'Fatal Error '.$lastError['message'];
            $error = $lastError;
            $trace = file(sys_get_temp_dir().'/phpspec_error.log');

            $traces = array();
            for( $i=0,$count=count($trace);$i < $count; $i++ ){
                $text = trim($trace[$i]);
                if(false!==($pos=strpos($text,'PHP '))){
                    $text = substr($text,$pos+4);
                }
                $traces[] = $text;
            }
            $event = ResultEvent::createError(
                $message,
                $error,
                null,
                $traces
            );
            Filesystem::serialize(Inspector::getResultFileName(),array($event));
        }
    }

}