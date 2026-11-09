<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(StoreSeeder::class);
        // Optional sample catalog (the products that were hardcoded in the React demo mode):
        //   php artisan db:seed --class=DemoProductsSeeder
    }
}
