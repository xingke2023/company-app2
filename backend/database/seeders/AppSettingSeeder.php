<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'miniprogram_title', 'value' => '进销存及CRM', 'label' => '小程序顶部标题', 'sort_order' => 10],
        ];

        foreach ($defaults as $row) {
            AppSetting::firstOrCreate(['key' => $row['key']], $row);
        }
    }
}
