<?php

namespace Database\Seeders;

use App\Models\DuesRate;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'association_name' => ['ADISADEL COLLEGE TEACHING STAFF WELFARE ASSOCIATION', 'string'],
            'institution_name' => ['Adisadel College', 'string'],
            'application_name' => ['Staff Welfare Dues', 'string'],
            'default_currency' => ['GHS', 'string'],
            'currency_symbol' => ['GHS', 'string'],
            'financial_year_start_month' => ['1', 'integer'],
            'session_timeout_minutes' => ['120', 'integer'],
            'system_mode' => ['production', 'string'],
        ];

        foreach ($settings as $key => [$value, $type]) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'type' => $type]);
        }

        DuesRate::query()->firstOrCreate(
            ['name' => 'Default monthly dues', 'effective_from' => '2024-01-01'],
            [
                'amount' => 20,
                'is_active' => true,
                'created_by' => User::query()->where('username', 'admin')->value('id'),
            ]
        );
    }
}
