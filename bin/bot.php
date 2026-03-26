<?php declare(strict_types=1);

namespace App\Presentation;

use App\Infrastructure\Telegram\CurlTelegramClient;
use App\Infrastructure\Telegram\MadelineProtoClient;
use App\Application\PostProcessor;
use App\Application\RuleBuilder;
use App\Domain\Specification\ContainsPhraseSpecification;
use App\Domain\Action\NotifyUserAction;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    (new Dotenv())->load(__DIR__ . '/../.env');
}

$logger = new Logger('bot');
$logger->pushHandler(new StreamHandler($_ENV['LOG_PATH'] ?? 'php://stdout', Logger::INFO));

$notificationClient = new CurlTelegramClient();
$parserClient = new MadelineProtoClient();

$searchPhrase = $_ENV['SEARCH_PHRASE'] ?? 'findme';

$rule = (new RuleBuilder())
    ->withCondition(new ContainsPhraseSpecification($searchPhrase))
    ->addAction(new NotifyUserAction($notificationClient, (int)$_ENV['NOTIFY_USER_ID'], $logger))
    ->build();

$processor = new PostProcessor([$rule]);

$offset = 0;
$targetChatId = (int)$_ENV['TARGET_CHAT_ID'];

$logger->info('Userbot успешно запущен (MadelineProto)', [
    'chat_id' => $targetChatId,
    'search_phrase' => $searchPhrase
]);

$consecutiveErrors = 0;
$lastHeartbeat = time();

while (true) {
    try {
        $updatesRaw = $parserClient->getUpdates($offset);

        if (time() - $lastHeartbeat >= 30) {
            $logger->info('Userbot heartbeat — polling continues');
            $lastHeartbeat = time();
        }

        foreach ($updatesRaw as $update) {
            $post = $parserClient->convertToPost($update);
            if ($post) {
                $logger->info('Обнаружен пост для обработки', [
                    'message_id' => $post->messageId,
                    'text_preview' => mb_substr($post->text, 0, 80) . '...'
                ]);
                $processor->process($post);
            }
            $offset = max($offset, $update['update_id'] ?? 0) + 1;
        }

        $consecutiveErrors = 0;
        sleep(1);

    } catch (\Throwable $e) {
        $consecutiveErrors++;
        $logger->error('Userbot ошибка', ['error' => $e->getMessage()]);
        if ($consecutiveErrors > 5) {
            sleep(60);
            $consecutiveErrors = 0;
        } else {
            sleep(10);
        }
    }
}
