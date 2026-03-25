<?php declare(strict_types=1);

namespace App\Application;

use App\Application\Rule;
use App\Domain\Action\ActionInterface;
use App\Domain\Specification\ConditionInterface;

final class RuleBuilder
{
    /**
     * @var ConditionInterface
     */
    private ConditionInterface $condition;

    /**
     * @var ActionInterface[]
     */
    private array $actions = [];

    /**
     * @param ConditionInterface $condition
     * @return $this
     */
    public function withCondition(ConditionInterface $condition): self
    {
        $this->condition = $condition;
        return $this;
    }

    /**
     * @param ActionInterface $action
     * @return $this
     */
    public function addAction(ActionInterface $action): self
    {
        $this->actions[] = $action;
        return $this;
    }

    /**
     * @return Rule
     */
    public function build(): Rule
    {
        return new Rule($this->condition, $this->actions);
    }
}
