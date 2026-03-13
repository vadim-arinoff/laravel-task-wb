<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\{Account, Sales, ApiService};
use App\Traits\ApiRequestTrait;

class FetchSales extends Command
{
    use ApiRequestTrait; //Метод 429
    protected $signature = 'fetch:sales';
    protected $description = 'Умная загрузка продаж для всех аккаунтов';

    public function handle()
    {
        $this->info('--- Старт синхронизации Продаж ---');

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
            $this->info("Обработка аккаунта: {$account->name}");

            // Достаем сам ключ токена из базы
            $token = $account->tokens->where('api_service_id', $service->id)->first()->value;

            // Ищем последнюю дату заказа именно для этого аккаунта
            $latestOrder = Sales::where('account_id', $account->id)
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

            $dateTo = now()->toDateString(); // По сегодняшний день
            $page = 1;
            $limit = 500;

            // Цикл пагинации (листаем страницы API)
            do {
                $this->info("  -> Запрашиваем страницу {$page}...");

                // используется метод из трейта
                $data = $this->makeRequestWithRetry("http://109.73.206.144:6969/api/sales", [
                    'dateFrom' => $dateFrom,
                    'dateTo'   => $dateTo,
                    'page'     => $page,
                    'limit'    => $limit,
                    'key'      => $token
                ]);

                // Если вернулся null или пустой массив данных - значит всё скачали, прерываем цикл
                if (!$data || empty($data['data'])) {
                    $this->info("  Данные закончились.");
                    break; 
                }

                // Сохраняем в БД, предотвращая затирание и дублирование
                foreach ($data['data'] as $item) {
                    Sales::updateOrCreate(
                        [
                            // Заказ с таким ID И у такого аккаунта
                            // (чтобы разные аккаунты могли иметь одинаковые ID заказов, если такое бывает)
                            'account_id' => $account->id,
                            'sale_id'       => $item['sale_id'] // Используем уникальный номер как идентификатор
                        ],
                        [
                        'g_number'            => $item['g_number'] ?? null,
                        'date'                => $item['date'] ?? null,
                        'last_change_date'    => $item['last_change_date'] ?? null,
                        'supplier_article'    => $item['supplier_article'] ?? null,
                        'tech_size'           => $item['tech_size'] ?? null,
                        'barcode'             => $item['barcode'] ?? null,
                        'total_price'         => $item['total_price'] ?? null,
                        'discount_percent'    => $item['discount_percent'] ?? null,
                        'is_supply'           => $item['is_supply'] ?? null,
                        'is_realization'      => $item['is_realization'] ?? null,
                        'promo_code_discount' => $item['promo_code_discount'] ?? null,
                        'warehouse_name'      => $item['warehouse_name'] ?? null,
                        'country_name'        => $item['country_name'] ?? null,
                        'oblast_okrug_name'   => $item['oblast_okrug_name'] ?? null,
                        'region_name'         => $item['region_name'] ?? null,
                        'income_id'           => $item['income_id'] ?? null,
                        'odid'                => $item['odid'] ?? null,
                        'spp'                 => $item['spp'] ?? null,
                        'for_pay'             => $item['for_pay'] ?? null,
                        'finished_price'      => $item['finished_price'] ?? null,
                        'price_with_disc'     => $item['price_with_disc'] ?? null,
                        'nm_id'               => $item['nm_id'] ?? null,
                        'subject'             => $item['subject'] ?? null,
                        'category'            => $item['category'] ?? null,
                        'brand'               => $item['brand'] ?? null,
                        'is_storno'           => $item['is_storno'] ?? null,
                        ]
                    );
                }

                $page++;

            } while (true);
        }

        $this->info('--- Синхронизация Заказов успешно завершена! ---');
    }
}