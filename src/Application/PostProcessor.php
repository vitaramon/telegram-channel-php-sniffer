<?php declare(strict_types=1);

namespace App\Application;

use App\Domain\Model\Post;

final class PostProcessor
{
    /**
     * @param Rule[] $rules
     */
    public function __construct(
        private readonly array $rules
    ) {}

    /**
     * @param Post $post
     * @return void
     */
    public function process(Post $post): void
    {
        foreach ($this->rules as $rule) {
            $rule->apply($post);
        }
    }
}
