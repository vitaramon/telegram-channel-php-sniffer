<?php declare(strict_types=1);

namespace App\Domain\Specification;

use App\Domain\Model\Post;

final class ContainsPhraseSpecification implements ConditionInterface
{
    /**
     * @param string $phrase
     */
    public function __construct(private readonly string $phrase) {}

    /**
     * @param Post $post
     * @return bool
     */
    public function isSatisfiedBy(Post $post): bool
    {
        return mb_stripos($post->text, $this->phrase) !== false;
    }
}
