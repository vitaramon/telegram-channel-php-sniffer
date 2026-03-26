<?php declare(strict_types=1);

namespace App\Infrastructure\Telegram;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use App\Domain\Model\Post;

final class MadelineProtoClient
{
    /**
     * @var API
     */
    private API $madeline;

    public function __construct()
    {
        $settings = new Settings();
        $settings->getAppInfo()->setApiId((int)$_ENV['API_ID']);
        $settings->getAppInfo()->setApiHash($_ENV['API_HASH']);

        $sessionPath = __DIR__ . '/../../../' . ($_ENV['SESSION_PATH'] ?? 'sessions/userbot.madeline');

        $this->madeline = new API($sessionPath, $settings);
        $this->madeline->start(); // первый запуск — интерактивный логин
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getUpdates(int $offset = 0, int $limit = 100): array
    {
        $updates = $this->madeline->getUpdates([
            'offset' => $offset,
            'limit' => $limit,
            'timeout' => 30
        ]);

        return $updates ?? [];
    }

    /**
     * @param array $update
     * @return Post|null
     */
    public function convertToPost(array $update): ?Post
    {
        $message = $update['message'] ?? $update['channel_post'] ?? null;
        if (!$message || !isset($message['text']) || (int)$message['chat']['id'] !== (int)$_ENV['TARGET_CHAT_ID']) {
            return null;
        }

        return new Post(
            $message['text'],
            (int)$message['chat']['id'],
            (int)$message['id'],
            (int)$message['date']
        );
    }
}
