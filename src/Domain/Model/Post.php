<?php declare(strict_types=1);

namespace App\Domain\Model;

final class Post
{
    /**
     * @param string $text
     * @param int $chatId
     * @param int $messageId
     * @param int $date
     */
    public function __construct(
        public readonly string $text,
        public readonly int $chatId,
        public readonly int $messageId,
        public readonly int $date
    ) {}
}
