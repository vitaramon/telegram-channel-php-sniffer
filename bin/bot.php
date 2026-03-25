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

// === Docker-friendly загрузка .env ===
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    (new Dotenv())->load($envPath);
}

$logger = new Logger('bot');
$logger->pushHandler(new StreamHandler($_ENV['LOG_PATH'] ?? 'php://stdout', Logger::INFO));

$client = new CurlTelegramClient();

$searchPhrase = $_ENV['SEARCH_PHRASE'] ?? 'findme';

$rule = (new RuleBuilder())
    ->withCondition(new ContainsPhraseSpecification($searchPhrase))
    ->addAction(new NotifyUserAction($client, (int)$_ENV['NOTIFY_USER_ID'], $logger))
    ->build();

$processor = new PostProcessor([$rule]);

$offset = 0;
$targetChatId = (int)$_ENV['TARGET_CHAT_ID'];

$logger->info('Бот успешно запущен и начинает polling', [
    'chat_id' => $targetChatId,
    'search_phrase' => $searchPhrase
]);

$consecutiveErrors = 0;
$lastHeartbeat = time();

while (true) {
    $updates = $client->getUpdates($offset);

    // Heartbeat: каждые 30 секунд показываем, что бот жив
    if (time() - $lastHeartbeat >= 30) {
        $logger->info('Bot heartbeat — polling continues', [
            'uptime' => gmdate('H:i:s', time() - ($lastHeartbeat - 30)),
            'last_offset' => $offset
        ]);
        $lastHeartbeat = time();
    }

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

    $updateCount = count($updates['result'] ?? []);
    if ($updateCount > 0) {
        $logger->info("Получено {$updateCount} обновлений от Telegram", ['offset' => $offset]);
    }

    foreach ($updates['result'] ?? [] as $update) {
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
                'message_id' => $post->messageId,
                'text_preview' => mb_substr($post->text, 0, 80) . '...'
            ]);

            $processor->process($post);
        }

        $offset = max($offset, (int)$update['update_id'] + 1);
    }

    sleep(1);
}