<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        StoreSetting::current(); // creates the single settings row with the defaults

        $categories = ['Suits', 'Blazers', 'Shirts', 'Trousers', 'Outerwear', 'Knitwear', 'Dresses', 'Shoes', 'Accessories'];

        foreach ($categories as $i => $name) {
            Category::updateOrCreate(['slug' => strtolower($name)], ['name' => $name, 'sort' => $i + 1]);
        }
    }
}
