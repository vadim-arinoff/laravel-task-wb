<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Sales;

class FetchSales extends Command
{
    protected $signature = 'fetch:sales';
    protected $description = 'Стягиваем продажи (sales)';

    public function handle()
    {
        $this->info('Начинаем загрузку продаж');

        $page = 1;
        $dateFrom = '2026-01-01';
        $dateTo = now()->toDateString();

        do {
            $this->info("Запрашиваем страницу: {$page}");

            $response = Http::get("http://109.73.206.144:6969/api/sales",[
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
                Sales::create([
                    'g_number'            => $item['g_number'],
                    'date'                => $item['date'],
                    'last_change_date'    => $item['last_change_date'],
                    'supplier_article'    => $item['supplier_article'],
                    'tech_size'           => $item['tech_size'],
                    'barcode'             => $item['barcode'],
                    'total_price'         => $item['total_price'],
                    'discount_percent'    => $item['discount_percent'],
                    'is_supply'           => $item['is_supply'],
                    'is_realization'      => $item['is_realization'],
                    'promo_code_discount' => $item['promo_code_discount'] ?? null,
                    'warehouse_name'      => $item['warehouse_name'],
                    'country_name'        => $item['country_name'],
                    'oblast_okrug_name'   => $item['oblast_okrug_name'],
                    'region_name'         => $item['region_name'],
                    'income_id'           => $item['income_id'],
                    'sale_id'             => $item['sale_id'],
                    'odid'                => $item['odid'] ?? null,
                    'spp'                 => $item['spp'],
                    'for_pay'             => $item['for_pay'],
                    'finished_price'      => $item['finished_price'],
                    'price_with_disc'     => $item['price_with_disc'],
                    'nm_id'               => $item['nm_id'],
                    'subject'             => $item['subject'],
                    'category'            => $item['category'],
                    'brand'               => $item['brand'],
                    'is_storno'           => $item['is_storno'] ?? null,
                ]);
            }

            $page++;

        } while (true);

        $this->info('Загрузка продаж завершена');
    }
}