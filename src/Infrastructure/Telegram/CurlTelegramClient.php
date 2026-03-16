<?php declare(strict_types=1);

namespace App\Infrastructure\Telegram;

use Symfony\Component\Dotenv\Dotenv;

final class CurlTelegramClient implements TelegramClientInterface
{
    /**
     * @var string
     */
    private readonly string $token;

    public function __construct()
    {
        (new Dotenv())->load(__DIR__ . '/../../../.env');
        $this->token = $_ENV['BOT_TOKEN'];
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getUpdates(int $offset = 0, int $limit = 100): array
    {
        $url = "https://api.telegram.org/bot{$this->token}/getUpdates?offset={$offset}&limit={$limit}&timeout=30";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 35);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno !== 0) {
            error_log("Telegram getUpdates failed: [$errno] $error");
            return ['ok' => false, 'description' => $error, 'result' => []];
        }

        return json_decode($response, true) ?? ['ok' => false, 'result' => []];
    }

    public function sendMessage(int $chatId, string $text): void
    {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno !== 0) {
            error_log("Telegram sendMessage failed: [$errno] $error | chatId: $chatId");
        }
    }
}
