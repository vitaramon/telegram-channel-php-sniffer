<?php declare(strict_types=1);

namespace App\Presentation;

use App\Infrastructure\Telegram\CurlTelegramClient;
use App\Application\PostProcessor;
use App\Application\RuleBuilder;
use App\Domain\Specification\ContainsPhraseSpecification;
use App\Domain\Action\NotifyUserAction;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once __DIR__ . '/../vendor/autoload.php';

(new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/../.env');

$logger = new Logger('bot');
$logger->pushHandler(new StreamHandler($_ENV['LOG_PATH'] ?? 'php://stdout', Logger::INFO));

$client = new CurlTelegramClient();

$rule = (new RuleBuilder())
    ->withCondition(new ContainsPhraseSpecification('мафия'))
    ->addAction(new NotifyUserAction($client, (int)$_ENV['NOTIFY_USER_ID'], $logger))
    ->build();

$processor = new PostProcessor([$rule]);

$offset = 0;
$targetChatId = (int)$_ENV['TARGET_CHAT_ID'];

$logger->info('Бот запущен и слушает чат', ['chat_id' => $targetChatId]);

while (true) {
    $updates = $client->getUpdates($offset);

    if (!$updates['ok'] ?? false) {
        $logger->error('Ошибка получения обновлений', ['response' => $updates]);
        sleep(5);
        continue;
    }

    foreach ($updates['result'] as $update) {
        $message = $update['message'] ?? null;
        if ($message && isset($message['text']) && (int)$message['chat']['id'] === $targetChatId) {
            $post = new \App\Domain\Model\Post(
                $message['text'],
                (int)$message['chat']['id'],
                (int)$message['message_id'],
                (int)$message['date']
            );

            $processor->process($post);
        }

        $offset = max($offset, (int)$update['update_id'] + 1);
    }

    sleep(1); // пауза между polling-запросами
}
