<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\{Account, ApiService, Stock};
use App\Traits\ApiRequestTrait;

class FetchStocks extends Command
{
    use ApiRequestTrait; // трейт 429

    protected $signature = 'fetch:stocks';
    protected $description = 'Стягиваем остатки со складов для всех аккаунтов';

    public function handle()
    {
        $this->info('--- Старт синхронизации Складов ---');

        // Ищем сервис Wildberries
        $service = ApiService::where('name', 'Wildberries API')->first();
        if (!$service) {
            $this->error('API Сервис не найден в базе!');
            return;
        }

        // Ищем аккаунты с настроенными токенами
        $accounts = Account::whereHas('tokens', function ($query) use ($service) {
            $query->where('api_service_id', $service->id);
        })->with('tokens')->get();

        if ($accounts->isEmpty()) {
            $this->warn('Не найдено аккаунтов с токенами.');
            return;
        }

        // Запускаем цикл по аккаунтам
        foreach ($accounts as $account) {
            $this->info("Обработка аккаунта: {$account->name}");

            // Достаем токен текущего аккаунта
            $token = $account->tokens->where('api_service_id', $service->id)->first()->value;

            // для складов дата сегодняшняя по тз
            $dateFrom = Carbon::now()->toDateString(); 
            $page = 1;
            $limit = 500;

            do {
                $this->info("  -> Запрашиваем страницу {$page}...");

                $data = $this->makeRequestWithRetry("http://109.73.206.144:6969/api/stocks", [
                    'dateFrom' => $dateFrom,
                    'page'     => $page,
                    'limit'    => $limit,
                    'key'      => $token
                ]);

                // Проверка на пустоту
                if (!$data || empty($data['data'])) {
                    $this->info("  Данные для аккаунта {$account->name} закончились.");
                    break;
                }

                // Сохраняем в БД
                foreach ($data['data'] as $item) {
                    Stock::updateOrCreate(
                        [
                            // Чтобы не было дублей, мы считаем запись уникальной, если совпадают: аккаунт + штрихкод + склад + дата.
                            // Если скрипт запустится дважды за сегодня, он просто обновит старую запись.
                            'account_id'     => $account->id,
                            'barcode'        => $item['barcode'] ?? 'no_barcode', 
                            'warehouse_name' => $item['warehouse_name'] ?? 'no_name',
                            'date'           => $item['date']
                        ],
                        [
                            // Все остальные поля для обновления/создания
                            'last_change_date'   => $item['last_change_date'] ?? null,
                            'supplier_article'   => $item['supplier_article'] ?? null,
                            'tech_size'          => $item['tech_size'] ?? null,
                            'quantity'           => $item['quantity'] ?? null,
                            'is_supply'          => $item['is_supply'] ?? null,
                            'is_realization'     => $item['is_realization'] ?? null,
                            'quantity_full'      => $item['quantity_full'] ?? null,
                            'in_way_to_client'   => $item['in_way_to_client'] ?? null,
                            'in_way_from_client' => $item['in_way_from_client'] ?? null,
                            'nm_id'              => $item['nm_id'] ?? null,
                            'subject'            => $item['subject'] ?? null,
                            'category'           => $item['category'] ?? null,
                            'brand'              => $item['brand'] ?? null,
                            'sc_code'            => $item['sc_code'] ?? null,
                            'price'              => $item['price'] ?? null,
                            'discount'           => $item['discount'] ?? null,
                        ]
                    );
                }

                $page++;

            } while (true);
        }

        $this->info('--- Синхронизация Складов успешно завершена! ---');
    }
}