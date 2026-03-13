<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\{Account, Incomes, ApiService};
use App\Traits\ApiRequestTrait; // Трейт 429 ошибки

class FetchIncomes extends Command
{
    use ApiRequestTrait;

    protected $signature = 'fetch:incomes';
    protected $description = 'Умная загрузка поступлений (incomes) для всех аккаунтов';

    public function handle()
    {
        $this->info('--- Старт синхронизации Поступлений ---');

        // Ищем сервис
        $service = ApiService::where('name', 'Wildberries API')->first();
        if (!$service) {
            $this->error('API Сервис не найден в базе!');
            return;
        }

        // аккаунты с токенами
        $accounts = Account::whereHas('tokens', function ($query) use ($service) {
            $query->where('api_service_id', $service->id);
        })->with('tokens')->get();

        if ($accounts->isEmpty()) {
            $this->warn('Не найдено аккаунтов с токенами.');
            return;
        }

        // Цикл по каждому аккаунту
        foreach ($accounts as $account) {
            $this->info("Обработка аккаунта: {$account->name}");

            // Достаем токен
            $token = $account->tokens->where('api_service_id', $service->id)->first()->value;

            // Ищем последнее поступление этого аккаунта
            $latestIncome = Incomes::where('account_id', $account->id)
                                   ->orderBy('date', 'desc')
                                   ->first();

            if ($latestIncome && $latestIncome->date) {
                // Если поступления уже были, начинаем с даты последнего скачанного
                $dateFrom = Carbon::parse($latestIncome->date)->toDateString();
                $this->info("Найдены старые данные. Качаем свежие начиная с: {$dateFrom}");
            } else {
                // Если база пустая
                $dateFrom = Carbon::now()->subDays(30)->toDateString();
                $this->info("База пуста. Качаем данные за последние 30 дней: {$dateFrom}");
            }

            $dateTo = now()->toDateString();
            $page = 1;
            $limit = 500;

            // Постраничная загрузка
            do {
                $this->info("  -> Запрашиваем страницу {$page}...");

                // метод из Трейта
                $data = $this->makeRequestWithRetry("http://109.73.206.144:6969/api/incomes", [
                    'dateFrom' => $dateFrom,
                    'dateTo'   => $dateTo,
                    'page'     => $page,
                    'limit'    => $limit,
                    'key'      => $token
                ]);

                if (!$data || empty($data['data'])) {
                    $this->info("  Данные закончились.");
                    break;
                }

                // Сохраняем в БД
                foreach ($data['data'] as $item) {
                    Incomes::updateOrCreate(
                        [
                            // Аккаунт + Номер поставки + Штрихкод товара
                            'account_id' => $account->id,
                            'income_id'  => $item['income_id'],
                            'barcode'    => $item['barcode']
                        ],
                        [
                            // Данные для обновления/сохранения
                            'number'           => $item['number'] ?? null,
                            'date'             => $item['date'] ?? null,
                            'last_change_date' => $item['last_change_date'] ?? null,
                            'supplier_article' => $item['supplier_article'] ?? null,
                            'tech_size'        => $item['tech_size'] ?? null,
                            'quantity'         => $item['quantity'] ?? null,
                            'total_price'      => $item['total_price'] ?? null,
                            'date_close'       => $item['date_close'] ?? null,
                            'warehouse_name'   => $item['warehouse_name'] ?? null,
                            'nm_id'            => $item['nm_id'] ?? null,
                        ]
                    );
                }

                $page++;

            } while (true);
        }

        $this->info('--- Синхронизация Поступлений успешно завершена! ---');
    }
}