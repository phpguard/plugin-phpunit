<?php

namespace spec\PhpGuard\Plugins\PHPUnit;

use PhpGuard\Application\Container\ContainerInterface;
use PhpGuard\Application\Event\ResultEvent;
use PhpGuard\Application\Log\Logger;
use PhpGuard\Application\Plugin\PluginInterface;
use PhpGuard\Application\Spec\ObjectBehavior;
use PhpGuard\Application\Util\Filesystem;
use PhpGuard\Application\Util\Runner;

use PhpGuard\Plugins\PHPUnit\Inspector;
use PhpGuard\Plugins\PHPUnit\PHPUnitPlugin;

use Prophecy\Argument;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class InspectorSpec extends ObjectBehavior
{
    protected $cacheFile;

    static protected $cwd;

    function let(
        ContainerInterface $container,
        Runner $runner,
        Process $process,
        PHPUnitPlugin $plugin,
        Logger $logger
    )
    {
        if(is_null(static::$cwd)){
            static::$cwd = getcwd();
        }

        //chdir(sys_get_temp_dir());

        $this->cacheFile = Inspector::getResultFileName();
        @unlink($this->cacheFile);
        $runner->setContainer($container);
        $runner->run(Argument::any())
            ->willReturn($process);
        $runner->findExecutable('phpunit')
            ->willReturn('phpunit');
        $container->get('runner')->willReturn($runner);
        $container->get('plugins.phpunit')->willReturn($plugin);
        $container->get('logger')->willReturn($logger);

        $container->setParameter('application.exit_code',Argument::any())
            ->willReturn(true);

        $plugin->getOptions()
            ->willReturn(array(
                'cli'=>'--some-options',
                'all_after_pass' => false,
                'run_all_cli' => '--some-run-all'
            ))
        ;
        $plugin->getTitle()
            ->willReturn('phpunit')
        ;
        $this->setContainer($container);
    }

    function letgo()
    {
        //chdir(static::$cwd);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('PhpGuard\Plugins\PHPUnit\Inspector');
    }

    function it_should_run_with_paths(
        Runner $runner,
        Process $process
    )
    {
        $event = ResultEvent::createSucceed('Success');
        Filesystem::create()->serialize($this->cacheFile,array(
            'key' => $event,
        ));
        $runner->run(Argument::any())
            ->willReturn($process)
            ->shouldBeCalled();
        $results = $this->run(array('some_path'));
        $results->getResults()->shouldHaveCount(1);
    }

    function it_should_run_all_after_pass(
        Runner $runner,
        Process $process,
        PluginInterface $plugin,
        ContainerInterface $container
    )
    {
        $plugin->getOptions()
            ->willReturn(array(
                'cli'   =>  '--some-options',
                'all_after_pass' => true,
                'run_all_cli' => '--some-run-all'
            ))
        ;
        $this->beConstructedWith();
        $this->setContainer($container);

        $event = ResultEvent::createSucceed('Success');
        Filesystem::create()->serialize($this->cacheFile,array(
            'key' => $event,
        ));
        $process->getExitCode()->willReturn(0);
        $runner->run(Argument::any())
            ->willReturn($process)
            ->shouldBeCalled()
        ;

        $results = $this->run(array('some_path'))->getResults();
        $results->shouldHaveCount(2);
        $results->shouldHaveKey('all_after_pass');


    }

    function its_runAll_should_returns_only_failed_or_broken_tests(
        Runner $runner
    )
    {
        Filesystem::create()->serialize($this->cacheFile,array(
            'succeed' => ResultEvent::createSucceed('Success'),
            'failed' => ResultEvent::createFailed('Failed'),
            'broken' => ResultEvent::createBroken('Broken'),
        ));
        $runner->run(Argument::any())
            ->shouldBeCalled();

        $results = $this->runAll();
        $results->getResults()->shouldHaveCount(2);
    }

    function it_should_keep_failed_test_to_run(
        Runner $runner
    )
    {
        $failed = ResultEvent::createFailed('Failed',array(
            'file' => 'some_file'
        ));
        $success = ResultEvent::createSucceed('Success');

        Filesystem::create()->serialize($this->cacheFile,array(
            'failed' => $failed,
            'success' => $success
        ));

        $this->runAll()->getResults()->shouldHaveCount(1);

        $runner->run(Argument::that(function(ProcessBuilder $builder){
            $line = $builder->getProcess()->getCommandLine();
            return false!== strpos($line,'some_file');
        }))
            ->shouldBeCalled()
        ;
        $this->runAll();
    }

    function its_runAll_should_set_application_exit_code_if_results_has_failed_or_broken(
        ContainerInterface $container
    )
    {
        $failed = ResultEvent::createFailed('Failed',array(
            'file' => 'some_file'
        ));
        $success = ResultEvent::createSucceed('Success');

        Filesystem::create()->serialize($this->cacheFile,array(
            'failed' => $failed,
            'success' => $success
        ));
        $container->setParameter('application.exit_code',ResultEvent::FAILED)
            ->shouldBeCalled()
        ;

        $this->runAll();
    }

    function it_throws_when_result_file_not_exists()
    {
        $this->shouldThrow('RuntimeException')
            ->duringRunAll();
    }
}