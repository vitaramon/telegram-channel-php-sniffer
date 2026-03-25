<?php declare(strict_types=1);

namespace App\Infrastructure\Telegram;

final class CurlTelegramClient implements TelegramClientInterface
{
    /**
     * @var string
     */
    private readonly string $token;

    public function __construct()
    {
        $this->token = getenv('BOT_TOKEN') ?: $_ENV['BOT_TOKEN'] ?? '';

        if (empty($this->token)) {
            throw new \RuntimeException('BOT_TOKEN is not set in environment');
        }
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getUpdates(int $offset = 0, int $limit = 100): array
    {
        $maxRetries = 3;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $url = "https://api.telegram.org/bot{$this->token}/getUpdates?offset={$offset}&limit={$limit}&timeout=30";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 40);           // увеличено
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 12);
            // === FIX TLS unexpected EOF ===
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
            curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'HIGH:!aNULL:!MD5:!RC4');

            $response = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($errno === 0) {
                return json_decode($response, true) ?? ['ok' => false, 'result' => []];
            }

            error_log("Telegram getUpdates attempt {$attempt}/{$maxRetries} failed: [{$errno}] {$error}");
            if ($attempt < $maxRetries) {
                sleep(2 ** $attempt); // exponential backoff
            }
        }

        return ['ok' => false, 'description' => 'All retry attempts failed', 'result' => []];
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'HIGH:!aNULL:!MD5:!RC4');

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno !== 0) {
            error_log("Telegram sendMessage failed: [{$errno}] {$error} | chatId: {$chatId}");
        }
    }
}