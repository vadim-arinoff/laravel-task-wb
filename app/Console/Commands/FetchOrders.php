<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Orders;

class FetchOrders extends Command
{
    protected $signature = 'fetch:orders';
    protected $description = 'Стягиваем заказы (orders)';

    public function handle()
    {
        $this->info('Начинаем загрузку заказов');

        $page = 1;
        $dateFrom = '2026-01-01';
        $dateTo = now()->toDateString();

        do {
            $this->info("Запрашиваем страницу: {$page}");

            $response = Http::get("http://109.73.206.144:6969/api/orders",[
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
                Orders::create([
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
                    'odid'             => $item['odid'] ?? null,
                    'nm_id'            => $item['nm_id'],
                    'subject'          => $item['subject'],
                    'category'         => $item['category'],
                    'brand'            => $item['brand'],
                    'is_cancel'        => $item['is_cancel'],
                    'cancel_dt'        => $item['cancel_dt'] ?? null,
                ]);
            }

            $page++;

        } while (true);

        $this->info('Загрузка заказов завершена');
    }
}