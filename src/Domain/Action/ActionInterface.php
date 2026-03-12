<?php declare(strict_types=1);

namespace App\Domain\Action;

use App\Domain\Model\Post;

interface ActionInterface
{
    /**
     * @param Post $post
     * @return void
     */
    public function execute(Post $post): void;
}
