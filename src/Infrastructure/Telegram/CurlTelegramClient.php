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
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? ['ok' => false, 'result' => []];
    }

    /**
     * @param int $chatId
     * @param string $text
     * @return void
     */
    public function sendMessage(int $chatId, string $text): void
    {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";
        $data = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML'];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_exec($ch);
        curl_close($ch);
    }
}
