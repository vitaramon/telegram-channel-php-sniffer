<?php declare(strict_types=1);

namespace App\Application;

use App\Domain\Specification\ConditionInterface;
use App\Domain\Action\ActionInterface;
use App\Domain\Model\Post;

final class Rule
{
    /**
     * @param ConditionInterface $condition
     * @param ActionInterface[] $actions
     */
    public function __construct(
        private readonly ConditionInterface $condition,
        private readonly array $actions
    ) {}

    /**
     * @param Post $post
     * @return void
     */
    public function apply(Post $post): void
    {
        if ($this->condition->isSatisfiedBy($post)) {
            foreach ($this->actions as $action) {
                $action->execute($post);
            }
        }
    }
}
