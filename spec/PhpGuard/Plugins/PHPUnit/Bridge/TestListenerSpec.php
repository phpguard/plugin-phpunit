<?php

namespace spec\PhpGuard\Plugins\PHPUnit\Bridge;

use PhpGuard\Application\Bridge\CodeCoverageRunner;
use PhpGuard\Application\Container;
use PhpGuard\Application\Spec\ObjectBehavior;
use Prophecy\Argument;
use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_TestSuite;

class MockTestCase extends PHPUnit_Framework_TestCase
{
    public function getKey()
    {

    }
}

class MockTestSuite extends PHPUnit_Framework_TestSuite
{
}

class TestListenerSpec extends ObjectBehavior
{
    protected $resultKey;

    function let(CodeCoverageRunner $coverageRunner, MockTestCase $test)
    {
        $test->getName(Argument::any())
            ->willReturn('test_name');
        $this->resultKey = 'c06a94b2b5948a9763b419b68ad280ba';
        $this->setCoverage($coverageRunner);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PHPUnit\Bridge\TestListener');
    }

    function it_should_start_coverage_test_started(
        CodeCoverageRunner $coverageRunner,
        MockTestCase $test
    )
    {
        $coverageRunner->start(Argument::containingString('test_name'))
            ->shouldBeCalled();
        $this->startTest($test);
    }

    function it_should_stop_coverage_when_test_ended(
        CodeCoverageRunner $coverageRunner,
        MockTestCase $test
    )
    {
        $coverageRunner->stop()
            ->shouldBeCalled();
        $this->endTest($test,0);
    }

    function it_should_add_broken_result_if_test_is_error(
        MockTestCase $test
    )
    {
        $e = new \Exception('Some Error');
        $this->addError($test,$e,0);
        $result = $this->getResults();
        $result = $result[0];
        $result->shouldBeBroken();
    }

    function it_should_add_failed_result_if_test_is_fail(
        MockTestCase $test,
        \PHPUnit_Framework_AssertionFailedError $assertionFailedError
    )
    {
        $this->addFailure($test,$assertionFailedError,0);
        $result = $this->getResults();
        $result = $result[0];
        $result->shouldBeFailed();
    }

    function it_should_add_failed_result_if_test_is_incomplete(
        MockTestCase $test,
        \PHPUnit_Framework_AssertionFailedError $assertionFailedError
    )
    {
        $this->addIncompleteTest($test,$assertionFailedError,0);
        $result = $this->getResults();
        $result = $result[0];
        $result->shouldBeFailed();
    }

    function it_should_add_failed_result_if_test_is_risky(
        MockTestCase $test
    )
    {
        $e = new \Exception('Some Risky');
        $this->addRiskyTest($test,$e,0);
        $this->shouldBeFailed();
        $result = $this->getResults();
        $result = $result[0];
        $result->shouldBeFailed();
    }

    function it_should_add_succeed_result_if_test_is_skipped(
        MockTestCase $test
    )
    {
        $e = new \Exception('Some Risky');
        $this->addSkippedTest($test,$e,0);
        $result = $this->getResults();
        $result = $result[0];
        $this->shouldNotBeFailed();
        $result->shouldBeSucceed();
    }

    function it_should_add_succeed_result_if_test_has_no_failed_results(
        MockTestSuite $suite
    )
    {
        // not processing for class::method
        $suite->getName()->willReturn('SomeClass::Foo');
        $this->endTestSuite($suite);
        $this->getResults()->shouldHaveCount(0);

        // not processing for unexistent class
        $suite->getName()->willReturn('SomeClass');
        $this->endTestSuite($suite);
        $this->getResults()->shouldHaveCount(0);

        $suite->getName()->willReturn(__CLASS__);

        $this->shouldNotBeFailed();

        $this->endTestSuite($suite);
        $result = $this->getResults();
        $result = $result[0];
        $result->shouldBeSucceed();
    }

    function it_should_not_process_phpunit_framework_warning_errors(
        \PHPUnit_Framework_Warning $error
    )
    {
        $this->addError($error,new \Exception('Some Warning'),0);
        $this->getResults()->shouldHaveCount(0);
    }

    function it_should_reset_failed_status_when_suite_started(
        MockTestSuite $suite,
        MockTestCase $test
    )
    {
        $this->addFailure($test, new \PHPUnit_Framework_AssertionFailedError(),0);
        $this->shouldBeFailed();

        $this->startTestSuite($suite);
        $this->shouldNotBeFailed();
    }
}