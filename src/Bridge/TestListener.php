<?php

/*
 * This file is part of the PhpGuard project.
 *
 * (c) Anthonius Munthi <me@itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpGuard\Plugins\PHPUnit\Bridge;

use Exception;
use PhpGuard\Application\Bridge\CodeCoverage\CodeCoverageSession;
use PhpGuard\Application\Event\ResultEvent;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;
use PHPUnit_Framework_AssertionFailedError;

/**
 * Class TestListener
 *
 */
class TestListener implements \PHPUnit_Framework_TestListener
{
    /**
     * @var ResultEvent[]
     */
    private $results = array();

    private $hasFailed = false;

    /**
     * @var CodeCoverageSession
     */
    private $coverage;

    public function setCoverage(CodeCoverageSession $coverage)
    {
        $this->coverage = $coverage;
    }

    public function addError(\PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $message = 'Error: %test_name%; '.$e->getMessage();
        $this->addResult($test,ResultEvent::BROKEN,$message,$e);
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        $message = "Failed: %test_name%";
        $this->addResult($test,ResultEvent::FAILED,$message,$e);
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $message = 'Incomplete: %test_name%';
        $this->addResult($test,ResultEvent::FAILED,$message,$e);
    }

    public function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $message = "Risky: %test_name%";
        $this->addResult($test,ResultEvent::FAILED,$message,$e);
    }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $message = "Skipped: %test_name%";
        $this->addResult($test,ResultEvent::SUCCEED,$message,$e);
    }

    public function startTest(PHPUnit_Framework_Test $test)
    {
        $name = strtr('%test%::%name%', array(
            '%test%' => get_class($test),
            '%name%' =>$test->getName(),
        ));
        $this->coverage->start($name);

        return;
    }

    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        $this->coverage->stop();

        return;
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $this->hasFailed = false;

        return;
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $name = $suite->getName();
        if (false!==strpos($name,'::')) {
            return;
        }

        if (!class_exists($name)) {
            return;
        }

        if (!$this->isFailed()) {
            $r = new \ReflectionClass($name);
            $event = new ResultEvent(
                ResultEvent::SUCCEED,
                'Succeed: <highlight>'.$name.'</highlight>',
                array(
                    'file' => $r->getFileName(),
                )
            );

            $this->results[] = $event;
        }
    }

    /**
     * @param mixed      $test
     * @param int        $result
     * @param string     $message
     * @param \Exception $exception
     *
     * @return void
     */
    private function addResult($test,$result,$message,$exception=null)
    {

        $class = get_class($test);
        $name = $test->getName(true);
        if (false!==strpos($class,'PHPUnit_Framework')) {
            return;
        }

        if ($result>ResultEvent::SUCCEED) {
            $this->hasFailed = true;
        }
        $message = strtr($message,array(
            '%test_name%' => $class.'::'.$name
        ));
        $r = new \ReflectionClass($class);
        $arguments = array(
            'file' => realpath($r->getFileName()),
        );
        $event = new ResultEvent($result,$message,$arguments,$exception);
        $this->results[] = $event;
    }

    /**
     * @return ResultEvent[]
     */
    public function getResults()
    {
        return $this->results;
    }

    public function isFailed()
    {
        return $this->hasFailed;
    }
}
