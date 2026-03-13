<?php

use Illuminate\Support\Facades\Schedule;

/*
Здесь мы настраиваем расписание (Scheduler) для наших команд.
Работодатель просил: "организовать ежедневное обновление данных дважды в день".
twiceDaily(1, 13) запускает скрипт в 00:00 ночи и в 12:00 дня.
*/

Schedule::command('fetch:stocks')->twiceDaily(0, 12);
Schedule::command('fetch:sales')->twiceDaily(0, 12);
Schedule::command('fetch:orders')->twiceDaily(0, 12);
Schedule::command('fetch:incomes')->twiceDaily(0, 12);