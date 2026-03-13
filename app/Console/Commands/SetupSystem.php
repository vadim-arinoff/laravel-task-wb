<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Company, Account, ApiService, TokenType, Token};

class SetupSystem extends Command
{
    protected $signature = 'system:setup';
    protected $description = 'Интерактивное создание базовых сущностей системы';

    public function handle()
    {
        $this->info('--- Настройка системы парсинга ---');

        // Создаем или находим API Сервис
        $serviceName = $this->ask('Введите название API Сервиса (например: Wildberries API)', 'Wildberries API');
        $service = ApiService::firstOrCreate(['name' => $serviceName]);

        // Создаем или находим Тип Токена
        $typeName = $this->ask('Введите тип токена (например: api-key, bearer)', 'api-key');
        $tokenType = TokenType::firstOrCreate(['name' => $typeName]);

        // Связываем Сервис и Тип Токена (если еще не связаны)
        $service->tokenTypes()->syncWithoutDetaching([$tokenType->id]);

        // Создаем Компанию
        $companyName = $this->ask('Введите название Компании (например: Default Company)', 'Моя Компания');
        $company = Company::firstOrCreate(['name' => $companyName]);

        // Создаем Аккаунт
        $accountName = $this->ask('Введите название Аккаунта', 'Основной аккаунт');
        $account = Account::firstOrCreate([
            'name' => $accountName,
            'company_id' => $company->id
        ]);

        // Создаем Токен
        $tokenValue = $this->ask('Введите сам Токен (ключ доступа)');
        
        Token::updateOrCreate(
            [
                'account_id' => $account->id,
                'api_service_id' => $service->id,
            ],
            [
                'token_type_id' => $tokenType->id,
                'value' => $tokenValue
            ]
        );

        $this->info('Успешно! Все сущности созданы и связаны.');
    }
}