<?php


namespace Madkom\ContinuousIntegration\PrivateGitlabRunner\Domain\Configuration;


use Madkom\ContinuousIntegration\PrivateGitlabRunner\Domain\PrivateRunnerException;

trait VariableAwareTrait
{
    /**
     * @var  Variable[]
     */
    private $variables;

    /**
     * @return array|Variable[]
     */
    public function variables()
    {
        return $this->variables;
    }

    /**
     * @param array|Variable[] $variables
     *
     * @throws PrivateRunnerException
     */
    private function setVariables(array $variables)
    {
        foreach ($variables as $variable) {
            if (!($variable instanceof Variable)) {
                throw new PrivateRunnerException("Passed variables should have type of variable");
            }
        }

        $this->variables = $variables;
    }
}
