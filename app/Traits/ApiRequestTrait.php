<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait ApiRequestTrait
{

    // Универсальный метод для отправки запроса с защитой от 429

    protected function makeRequestWithRetry($url, $params)
    {
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $response = Http::get($url, $params);

            if ($response->status() === 429) {
                $attempt++;
                $this->warn("    [!] Ошибка 429 (Too Many Requests). Ждем 60 сек... (Попытка {$attempt} из {$maxRetries})");
                sleep(60);
                continue;
            }

            if ($response->successful()) {
                return $response->json();
            }

            $this->error("    [X] Ошибка API: Статус {$response->status()}");
            return null;
        }

        return null;
    }
}