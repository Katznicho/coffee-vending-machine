<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@vendormachine.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'operator@vendormachine.test'],
            [
                'name' => 'Operator',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        Machine::updateOrCreate(
            ['machine_id' => '00000022481'],
            [
                'name' => 'Ranchers Finest Coffee Machine',
                'location' => 'Kampala',
                'secret_key' => env('VENDING_DEMO_SECRET_KEY', 'demo-secret-key-change-me'),
                'status' => 'active',
            ]
        );

        $settings = \App\Models\CellulantSetting::current();

        $settings->update([
            'sandbox_base_url' => $settings->sandbox_base_url === 'https://api-approval.tingg.africa'
                ? 'https://payments-instore.sandbox.tingg.africa'
                : $settings->sandbox_base_url,
            'production_base_url' => $settings->production_base_url ?: 'https://payments.instore.tingg.africa',
            'country_code' => 'UGA',
            'request_origin_code' => $settings->request_origin_code ?: 'TINGG_INSTORE_INTEGRATION',
            'oauth_scope' => $settings->oauth_scope ?: 'read',
        ]);
    }
}
