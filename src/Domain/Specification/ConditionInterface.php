<?php declare(strict_types=1);

namespace App\Domain\Specification;

use App\Domain\Model\Post;

interface ConditionInterface
{
    /**
     * @param Post $post
     * @return bool
     */
    public function isSatisfiedBy(Post $post): bool;
}
