<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Stock;

class FetchStocks extends Command
{
    protected $signature = 'fetch:stocks';
    protected $description = 'Стягиваем остатки со складов';

    public function handle()
    {
        $this->info('Начинаем загрузку складов');

        $page = 1;
        $limit = 500;
        $dateFrom = now()->toDateString(); //только за текущий день

        do {
            $this->info("Запрашиваем страницу: {$page}");

            // запрос к api
            $response = Http::get("http://109.73.206.144:6969/api/stocks",[
                'dateFrom' => $dateFrom,
                'page'     => $page,
                'limit'    => $limit,
                'key'      => 'E6kUTYrYwZq2tN4QEtyzsbEBk3ie'
            ]);

            // JSON в массив
            $data = $response->json();

            if (!isset($data['data']) || empty($data['data'])) {
                $this->info('Данные кончились');
                break;
            }

            foreach ($data['data'] as $item) {
                Stock::create([
                    'date'               => $item['date'],
                    'last_change_date'   => $item['last_change_date'] ?? null,
                    'supplier_article'   => $item['supplier_article'] ?? null,
                    'tech_size'          => $item['tech_size'] ?? null,
                    'barcode'            => $item['barcode'],
                    'quantity'           => $item['quantity'],
                    'is_supply'          => $item['is_supply'] ?? null,
                    'is_realization'     => $item['is_realization'] ?? null,
                    'quantity_full'      => $item['quantity_full'] ?? null,
                    'warehouse_name'     => $item['warehouse_name'],
                    'in_way_to_client'   => $item['in_way_to_client'] ?? null,
                    'in_way_from_client' => $item['in_way_from_client'] ?? null,
                    'nm_id'              => $item['nm_id'],
                    'subject'            => $item['subject'] ?? null,
                    'category'           => $item['category'] ?? null,
                    'brand'              => $item['brand'] ?? null,
                    'sc_code'            => $item['sc_code'] ?? null,
                    'price'              => $item['price'] ?? null,
                    'discount'           => $item['discount'] ?? null,
                ]);
            }

            $page++;

        } while (true);

        $this->info('Загрузка складов успешна');
    }
}