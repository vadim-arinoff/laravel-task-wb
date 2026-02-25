<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Incomes;

class FetchIncomes extends Command
{
    protected $signature = 'fetch:incomes';
    protected $description = 'Стягиваем поступления (incomes)';

    public function handle()
    {
        $this->info('Начинаем загрузку поступлений');

        $page = 1;
        $dateFrom = '2026-01-01';
        $dateTo = now()->toDateString();

        do {
            $this->info("Запрашиваем страницу: {$page}");

            $response = Http::get("http://109.73.206.144:6969/api/incomes",[
                'dateFrom' => $dateFrom,
                'dateTo'   => $dateTo,
                'page'     => $page,
                'limit'    => 500,
                'key'      => 'E6kUTYrYwZq2tN4QEtyzsbEBk3ie'
            ]);

            $data = $response->json();

            if (empty($data['data'])) {
                $this->info('Данные закончились');
                break;
            }

            foreach ($data['data'] as $item) {
                Incomes::create([
                    'income_id'        => $item['income_id'],
                    'number'           => $item['number'] ?? null,
                    'date'             => $item['date'],
                    'last_change_date' => $item['last_change_date'],
                    'supplier_article' => $item['supplier_article'],
                    'tech_size'        => $item['tech_size'],
                    'barcode'          => $item['barcode'],
                    'quantity'         => $item['quantity'],
                    'total_price'      => $item['total_price'] ?? null,
                    'date_close'       => $item['date_close'],
                    'warehouse_name'   => $item['warehouse_name'],
                    'nm_id'            => $item['nm_id'],
                ]);
            }

            $page++;

        } while (true);

        $this->info('Загрузка поступлений завершена');
    }
}