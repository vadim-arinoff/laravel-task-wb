<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\{Account, Orders, ApiService};
use App\Traits\ApiRequestTrait;

class FetchOrders extends Command
{
    use ApiRequestTrait; //Метод 429
    protected $signature = 'fetch:orders';
    protected $description = 'Умная загрузка заказов для всех аккаунтов';

    public function handle()
    {
        $this->info('--- Старт синхронизации Заказов ---');

        // Ищем в базе наш API Сервис
        $service = ApiService::where('name', 'Wildberries API')->first();
        if (!$service) {
            $this->error('API Сервис "Wildberries API" не найден в базе!');
            return;
        }

        // Достаем аккаунты, у которых есть токен для этого сервиса
        $accounts = Account::whereHas('tokens', function ($query) use ($service) {
            $query->where('api_service_id', $service->id);
        })->with('tokens')->get();

        if ($accounts->isEmpty()) {
            $this->warn('Не найдено ни одного аккаунта с настроенным токеном.');
            return;
        }

        // Запускаем цикл по каждому аккаунту
        foreach ($accounts as $account) {
            $this->info("Обработка аккаунта: {$account->name} (ID: {$account->id})");

            // Достаем сам ключ токена из базы
            $token = $account->tokens->where('api_service_id', $service->id)->first()->value;

            // Ищем последнюю дату заказа именно для этого аккаунта
            $latestOrder = Orders::where('account_id', $account->id)
                                ->orderBy('date', 'desc')
                                ->first();

            // Если в базе уже есть заказы - берем дату последнего.
            // Если база пустая (первый запуск) - качаем, например, за последний месяц.
            if ($latestOrder && $latestOrder->date) {
                // Carbon помогает работать с датами. Мы парсим дату из БД.
                $dateFrom = Carbon::parse($latestOrder->date)->toDateString();
                $this->info("Найдены старые данные. Качаем свежие начиная с: {$dateFrom}");
            } else {
                $dateFrom = Carbon::now()->subDays(30)->toDateString();
                $this->info("База пуста. Качаем данные за последние 30 дней: {$dateFrom}");
            }

            $dateTo = Carbon::now()->toDateString(); // По сегодняшний день
            $page = 1;
            $limit = 500;

            // Цикл пагинации (листаем страницы API)
            do {
                $this->info("  -> Запрашиваем страницу {$page}...");

                // используется метод из трейта
                $data = $this->makeRequestWithRetry("http://109.73.206.144:6969/api/orders", [
                    'dateFrom' => $dateFrom,
                    'dateTo'   => $dateTo,
                    'page'     => $page,
                    'limit'    => $limit,
                    'key'      => $token
                ]);

                // Если вернулся null или пустой массив данных - значит всё скачали, прерываем цикл
                if (!$data || empty($data['data'])) {
                    $this->info("  Данные для аккаунта {$account->name} закончились.");
                    break; 
                }

                // Сохраняем в БД, предотвращая затирание и дублирование
                foreach ($data['data'] as $item) {
                    Orders::updateOrCreate(
                        [
                            // Заказ с таким ID И у такого аккаунта
                            // (чтобы разные аккаунты могли иметь одинаковые ID заказов, если такое бывает)
                            'account_id' => $account->id,
                            'odid'       => $item['odid'] ?? $item['g_number'] // Используем уникальный номер как идентификатор
                        ],
                        [
                        'g_number'         => $item['g_number'],
                        'date'             => $item['date'],
                        'last_change_date' => $item['last_change_date'],
                        'supplier_article' => $item['supplier_article'],
                        'tech_size'        => $item['tech_size'],
                        'barcode'          => $item['barcode'],
                        'total_price'      => $item['total_price'],
                        'discount_percent' => $item['discount_percent'],
                        'warehouse_name'   => $item['warehouse_name'],
                        'oblast'           => $item['oblast'],
                        'income_id'        => $item['income_id'],
                        'nm_id'            => $item['nm_id'],
                        'subject'          => $item['subject'],
                        'category'         => $item['category'],
                        'brand'            => $item['brand'],
                        'is_cancel'        => $item['is_cancel'],
                        'cancel_dt'        => $item['cancel_dt'] ?? null,
                        ]
                    );
                }

                $page++;

            } while (true);
        }

        $this->info('--- Синхронизация Заказов успешно завершена! ---');
    }
}