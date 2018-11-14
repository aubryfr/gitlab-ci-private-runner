<?php

namespace Madkom\ContinuousIntegration\PrivateGitlabRunner\Domain\Configuration;

/**
 * Class JobFactory
 * @package Madkom\ContinuousIntegration\PrivateGitlabRunner\Domain\Configuration
 * @author  Dariusz Gafka <d.gafka@madkom.pl>
 */
class JobFactory
{

    /**
     * Job constructor.
     *
     * @param string $jobName
     * @param string $imageName
     * @param string $stageName
     * @param array|Variable[] $variables
     * @param string[] $scripts
     * @param string[] $exceptList
     * @param string[] $onlyList
     *
     * @return Job
     */
    public function create($jobName, $imageName, $stageName, $variables, $scripts, $exceptList, $onlyList)
    {
        return new Job(
            $jobName,
            $imageName,
            new Stage($stageName),
            $variables,
            $scripts,
            $exceptList ? $exceptList : [],
            $onlyList ? $onlyList : []
        );
    }
}
