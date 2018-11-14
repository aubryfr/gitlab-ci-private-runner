<?php

namespace Madkom\ContinuousIntegration\PrivateGitlabRunner\Domain\Runner;

use Madkom\ContinuousIntegration\PrivateGitlabRunner\Domain\Configuration\GitlabCIConfiguration;
use Madkom\ContinuousIntegration\PrivateGitlabRunner\Domain\Configuration\GitlabCIConfigurationFactory;
use Madkom\ContinuousIntegration\PrivateGitlabRunner\Domain\Configuration\Job;
use Madkom\ContinuousIntegration\PrivateGitlabRunner\Domain\Configuration\Variable;
use Madkom\ContinuousIntegration\PrivateGitlabRunner\Domain\Docker\ConsoleCommandFactory;
use Madkom\ContinuousIntegration\PrivateGitlabRunner\Domain\PrivateRunnerException;

/**
 * Class ParallelJobRunner
 * @package Madkom\ContinuousIntegration\PrivateGitlabRunner\Domain\Runner
 * @author  Dariusz Gafka <d.gafka@madkom.pl>
 */
class ParallelJobRunner
{
    /**
     * @var ProcessRunner
     */
    private $processRunner;
    /**
     * @var GitlabCIConfigurationFactory
     */
    private $gitlabCIConfigurationFactory;
    /**
     * @var ConsoleCommandFactory
     */
    private $consoleCommandFactory;

    /**
     * Bla constructor.
     *
     * @param ProcessRunner         $processRunner
     * @param ConsoleCommandFactory $consoleCommandFactory
     * @param GitlabCIConfigurationFactory $gitlabCIConfigurationFactory
     */
    public function __construct(ProcessRunner $processRunner, ConsoleCommandFactory $consoleCommandFactory, GitlabCIConfigurationFactory $gitlabCIConfigurationFactory)
    {
        $this->processRunner                = $processRunner;
        $this->consoleCommandFactory        = $consoleCommandFactory;
        $this->gitlabCIConfigurationFactory = $gitlabCIConfigurationFactory;
    }

    /**
     * Run passed jobs
     *
     * @param string[]  $jobNames
     * @param string    $gitlabCiPath
     * @param string    $refName
     * @param null|int  $sleep
     * @param array     $volumes
     *
     * @throws PrivateRunnerException
     */
    public function runJobs(array $jobNames, $gitlabCiPath, $refName, $sleep = null, array $volumes = [])
    {
        /** @var Process[] $runningProcesses */
        $runningProcesses = [];
        $projectRootPath  = dirname($gitlabCiPath);
        $gitlabCIConfiguration = $this->gitlabCIConfigurationFactory->createFromYaml($gitlabCiPath);

        foreach ($jobNames as $jobName) {
            $job     = $gitlabCIConfiguration->getJob($jobName);
            $variables = $this->resolveVariables($job, $gitlabCIConfiguration);
            $command = $this->consoleCommandFactory->createDockerRunCommand(
                $job, $variables, $projectRootPath, $refName, $sleep, $volumes
            );

            $process = $this->processRunner->runProcess($job, $command);
            $runningProcesses[$jobName] = $process;
        }

        $errorProcesses = [];
        foreach ($runningProcesses as $jobName => $process) {
            while ($process->isRunning()) {}

            if (!$process->isSuccessful()) {
                $errorProcesses[] = $jobName;
            }
        }

        if ($errorProcesses) {
            $jobNamesToString = implode(", ", $errorProcesses);
            throw new PrivateRunnerException("Failed jobs: {$jobNamesToString}");
        }
    }

    private function resolveVariables(Job $job, GitlabCIConfiguration $gitlabCIConfiguration)
    {
        // Resolve if local variable (a local variable could reference another one)
        /** @var Variable[] $variables */
        $variables = [];

        foreach ($gitlabCIConfiguration->variables() as $pipelineVariable) {
            $variables[$pipelineVariable->key()] = $pipelineVariable;
        }

        foreach ($job->variables() as $jobVariable) {
//            var_dump($jobVariable->value());
            $variable = $jobVariable;
            // If the value contains variable references
            if (strpos($jobVariable->value(), '$') !== false) {
                $value = $jobVariable->value();
                $value = preg_replace('/\$([\w_]+)/i', '${\\1}', $value);
//                var_dump($value);
                while (preg_match('/\$\{([\w_]+)\}/', $value, $matches) === 1) {
                    $subVarToken = $matches[0];
                    $varName = $matches[1];
                    $newSubVarValue = isset($variables[$varName]) ? $variables[$varName]->value() : '';
                    $value = str_replace($subVarToken, $newSubVarValue, $value);
                    $variable = new Variable($jobVariable->key(), $value);
//                    echo $value . PHP_EOL;
                }
            }
//            echo "------" . PHP_EOL . PHP_EOL;
            $variables[$jobVariable->key()] = $variable;
        }

        return $variables;
    }

    /**
     * Run all jobs in stage
     *
     * @param string    $stageName
     * @param string    $gitlabCiPath
     * @param string    $refName
     * @param null|int  $sleep
     * @param array     $volumes
     *
     * @throws PrivateRunnerException
     */
    public function runStage($stageName, $gitlabCiPath, $refName, $sleep = null, array $volumes = [])
    {
        $gitlabCIConfiguration = $this->gitlabCIConfigurationFactory->createFromYaml($gitlabCiPath);
        $jobNames = $gitlabCIConfiguration->getJobsForStage($stageName);
        
        $this->runJobs($jobNames, $gitlabCiPath, $refName, $sleep, $volumes);
    }
}
