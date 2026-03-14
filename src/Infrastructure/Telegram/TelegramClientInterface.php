<?php declare(strict_types=1);

namespace App\Infrastructure\Telegram;

interface TelegramClientInterface
{
    /**
     * @param int $offset
     * @param int $limit
     * @return array{ok: bool, result: array}
     */
    public function getUpdates(int $offset = 0, int $limit = 100): array;

    /**
     * @param int $chatId
     * @param string $text
     * @return void
     */
    public function sendMessage(int $chatId, string $text): void;
}
