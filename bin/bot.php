<?php declare(strict_types=1);

namespace App\Presentation;

use App\Infrastructure\Telegram\CurlTelegramClient;
use App\Application\PostProcessor;
use App\Application\RuleBuilder;
use App\Domain\Specification\ContainsPhraseSpecification;
use App\Domain\Action\NotifyUserAction;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

// === Docker-friendly загрузка .env (только если файл существует) ===
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    (new Dotenv())->load($envPath);
    // Для локального запуска без docker-compose
}
// В Docker env_file уже инжектит переменные — ничего не делаем

$logger = new Logger('bot');
$logger->pushHandler(new StreamHandler($_ENV['LOG_PATH'] ?? 'php://stdout', Logger::INFO));

$client = new CurlTelegramClient();

$rule = (new RuleBuilder())
    ->withCondition(new ContainsPhraseSpecification('Мафию'))
    ->addAction(new NotifyUserAction($client, (int)$_ENV['NOTIFY_USER_ID'], $logger))
    ->build();

$processor = new PostProcessor([$rule]);

$offset = 0;
$targetChatId = (int)$_ENV['TARGET_CHAT_ID'];

$logger->info('Бот запущен и слушает чат', ['chat_id' => $targetChatId]);

$consecutiveErrors = 0;

while (true) {
    $updates = $client->getUpdates($offset);

    if (!isset($updates['ok']) || !$updates['ok']) {
        $consecutiveErrors++;
        $logger->error('Ошибка получения обновлений', [
            'response' => $updates,
            'consecutive' => $consecutiveErrors
        ]);

        if ($consecutiveErrors > 5) {
            $logger->critical('Слишком много ошибок API, пауза 60 сек');
            sleep(60);
            $consecutiveErrors = 0;
        } else {
            sleep(10);
        }
        continue;
    }

    $consecutiveErrors = 0;

    foreach ($updates['result'] as $update) {
        $message = $update['message'] ?? null;
        if ($message && isset($message['text']) && (int)$message['chat']['id'] === $targetChatId) {
            $post = new \App\Domain\Model\Post(
                $message['text'],
                (int)$message['chat']['id'],
                (int)$message['message_id'],
                (int)$message['date']
            );

            $logger->info('Обнаружен и обрабатывается пост', [
                'chat_id' => $post->chatId,
                'message_id' => $post->messageId
            ]);

            $processor->process($post);
        }

        $offset = max($offset, (int)$update['update_id'] + 1);
    }

    sleep(1);
}
