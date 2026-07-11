<?php

namespace Database\Seeders;

use App\Models\BenefitType;
use Illuminate\Database\Seeder;

class BenefitTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Bereavement', 'default_amount' => 500],
            ['name' => 'Hospitalisation', 'default_amount' => 300],
            ['name' => 'Marriage', 'default_amount' => 300],
            ['name' => 'Childbirth', 'default_amount' => 300],
            ['name' => 'Retirement', 'default_amount' => 1000],
            ['name' => 'Emergency Support', 'default_amount' => null],
            ['name' => 'Other', 'default_amount' => null],
        ] as $type) {
            BenefitType::query()->updateOrCreate(
                ['name' => $type['name']],
                [
                    'description' => $type['name'].' welfare benefit',
                    'default_amount' => $type['default_amount'],
                    'requires_approval' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
