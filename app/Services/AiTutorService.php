<?php

namespace App\Services;

use Config\Game;
use Config\Services;
use Throwable;

class AiTutorService
{
    private Game $config;

    public function __construct(?Game $config = null)
    {
        $this->config = $config ?? config(Game::class);
    }

    // null = AI gagal, controller menampilkan pesan fallback
    public function hint(array $challenge, string $userCode, string $testSummary, string $triggerType): ?string
    {
        $isAuto = $triggerType === 'auto';
        $rule   = $isAuto
            ? 'Jelaskan kesalahan lebih detail. Boleh memberi potongan kode kecil khusus bagian yang salah.'
            : 'Tunjukkan letak kesalahan dan konsep yang perlu dicek. Jangan menulis kode apa pun.';

        $system = 'Kamu tutor coding untuk pemula. Jawab dalam Bahasa Indonesia yang sederhana. '
            . 'Jangan pernah memberi kode jawaban utuh. ' . $rule;

        $user = "Instruksi tantangan:\n{$challenge['instruction']}\n\n"
            . "Solusi referensi (rahasia, jangan dikutip utuh):\n{$challenge['solution_code']}\n\n"
            . "Kode user:\n{$userCode}\n\n"
            . "Hasil test case:\n{$testSummary}";

        $answer = $this->ask($system, $user);

        // Penjaga kedua selain prompt
        return $answer === null ? null : $this->limitCode($answer, $isAuto ? $this->config->aiMaxCodeChars : 0);
    }

    public function ask(string $system, string $user): ?string
    {
        try {
            // false = instance baru, agar opsi tidak tercampur request lain
            $client = Services::curlrequest([
                'timeout'     => $this->config->aiTimeout,
                'http_errors' => false,
            ], null, null, false);

            $headers = ['Content-Type' => 'application/json'];

            if ($this->config->aiApiKey !== '') {
                $headers['Authorization'] = 'Bearer ' . $this->config->aiApiKey;
            }

            $response = $client->post(rtrim($this->config->aiBaseUrl, '/') . '/chat/completions', [
                'headers' => $headers,
                'json'    => [
                    'model'    => $this->config->aiModel,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $text = json_decode($response->getBody(), true)['choices'][0]['message']['content'] ?? null;

            return is_string($text) ? $text : null;
        } catch (Throwable $e) {
            log_message('error', 'AI tutor gagal: ' . $e->getMessage());

            return null;
        }
    }

    // Blok kode lebih panjang dari batas diganti catatan
    public function limitCode(string $text, int $maxChars): string
    {
        return preg_replace_callback(
            '/```.*?```/s',
            static fn (array $m) => strlen($m[0]) > $maxChars ? '[potongan kode disembunyikan]' : $m[0],
            $text,
        ) ?? $text;
    }
}
