<?php declare(strict_types=1);

namespace App\Domain\Action;

use App\Domain\Model\Post;
use App\Infrastructure\Telegram\TelegramClientInterface;
use Psr\Log\LoggerInterface;

final class NotifyUserAction implements ActionInterface
{
    /**
     * @param TelegramClientInterface $client
     * @param int $userId
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly TelegramClientInterface $client,
        private readonly int $userId,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * @param Post $post
     * @return void
     */
    public function execute(Post $post): void
    {
        $message = "🚨 Обнаружена фраза «мафия»!\n\n" .
            "Пост: https://t.me/c/" . substr((string)$post->chatId, 4) . "/" . $post->messageId . "\n" .
            "Время: " . date('Y-m-d H:i:s', $post->date);

        try {
            $this->client->sendMessage($this->userId, $message);
            $this->logger->info('NotifyUserAction executed', ['chatId' => $post->chatId, 'userId' => $this->userId]);
        } catch (\Throwable $e) {
            $this->logger->error('Notify failed', ['exception' => $e->getMessage()]);
        }
    }
}
