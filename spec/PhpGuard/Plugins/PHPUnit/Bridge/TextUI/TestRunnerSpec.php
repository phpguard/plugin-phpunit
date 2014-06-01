<?php

namespace spec\PhpGuard\Plugins\PHPUnit\Bridge\TextUI;

use PhpGuard\Application\Bridge\CodeCoverageRunner;
use PhpGuard\Plugins\PHPUnit\Bridge\TextUI\TestRunner;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MockTestSuite extends \PHPUnit_Framework_TestSuite
{

}

class MockPrinter extends \PHPUnit_TextUI_ResultPrinter
{
    public function write($buffer)
    {
        $this->output = $buffer;
    }
}

class TestRunnerSpec extends ObjectBehavior
{
    function let(
        \PHPUnit_Runner_TestSuiteLoader $loader
    )
    {
        $this->beConstructedWith($loader);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PHPUnit\Bridge\TextUI\TestRunner');
    }

    function it_should_save_coverage_session(
        MockTestSuite $suite,
        \PHPUnit_TextUI_ResultPrinter $printer
    )
    {
        $suite->run(Argument::any())
            ->shouldBeCalled()
            ->willReturn(null);

        $suite->setRunTestInSeparateProcess(Argument::any())
            ->shouldBeCalled();
        $this->doRun($suite,array(
            'printer' => $printer
        ));
    }

    function it_converts_comma_separated_argument_into_suite_files()
    {
        /* @var \PHPUnit_Framework_TestSuite $suite */
        $file = __FILE__.','.__FILE__;
        $suite = $this->getTest($file);
        $suite->getName()->shouldReturn('PhpGuard Unit Tests');
    }

}
