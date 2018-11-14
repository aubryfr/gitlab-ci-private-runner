<?php

namespace Madkom\ContinuousIntegration\PrivateGitlabRunner\Infrastructure\Service;

use Madkom\ContinuousIntegration\PrivateGitlabRunner\Domain\Configuration\Job;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;

/**
 * Class ProcessRunner
 * @package Madkom\ContinuousIntegration\PrivateGitlabRunner\Infrastructure\Service
 * @author  Dariusz Gafka <d.gafka@madkom.pl>
 */
class ProcessRunner implements \Madkom\ContinuousIntegration\PrivateGitlabRunner\Domain\Runner\ProcessRunner,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->setLogger(new NullLogger());
    }

    /**
     * @inheritdoc
     */
    public function runProcess(Job $job, $processCommand)
    {
        var_dump($processCommand);

        $process = new Process($processCommand);
        $process->setTimeout(720);

        $process->start(function ($type, $buffer) use ($job) {
            if (\Symfony\Component\Process\Process::ERR === $type) {
                echo "\e[33m{$job->jobName()}: \e[31mSTDERR > " . $buffer;
            } else {
                echo "\e[33m{$job->jobName()}: \e[32mSTDOUT > " . $buffer;
            }
        });

        return new SymfonyProcess($job, $process);
    }
}
