<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

// The sample catalog that used to be hardcoded in src/lib/demo-data.ts.
// Photos point at /stock/*.jpg inside the React app's public folder.
class DemoProductsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(StoreSeeder::class);

        $suit = ['46', '48', '50', '52', '54'];
        $top = ['S', 'M', 'L', 'XL', 'XXL'];
        $women = ['XS', 'S', 'M', 'L'];
        $shoe = ['40', '41', '42', '43', '44', '45'];

        $desc = 'Sharp tailoring with a clean, modern line. Fully lined, finished by hand and made to move with you from day to night.';

        // name, gender, category slug, price, compare_at, sizes, colours, featured, photos, style tags
        $rows = [
            ['Midnight Slim Suit', 'men', 'suits', 289, 349, $suit, ['Navy', 'Black'], true, ['1507679799987-c73779587ccf', '1617137968427-85924c800a22'], ['classic', 'modern', 'evening']],
            ['Windowpane Three-Piece', 'men', 'suits', 329, null, $suit, ['Blue check'], true, ['1594938298603-c8148c4dae35', '1598808503746-f34c53b9323e'], ['classic', 'statement']],
            ['Black Dinner Jacket', 'men', 'blazers', 219, null, $suit, ['Black'], true, ['1617127365659-c47fa864d8bc', '1618886614638-80e3c103d31a'], ['evening', 'classic']],
            ['Camel Soft Blazer', 'men', 'blazers', 189, 229, $suit, ['Camel', 'Navy'], false, ['1552374196-1ab2a1c593e8'], ['smart-casual', 'modern']],
            ['Poplin Dress Shirt', 'men', 'shirts', 49, null, $top, ['White', 'Sky'], false, ['1603252109303-2751441dd157', '1621072156002-e2fccdc0b176'], ['classic', 'smart-casual']],
            ['Pressed Shirt Trio', 'men', 'shirts', 119, 147, $top, ['Mixed'], false, ['1602810318383-e386cc2a3ccf'], ['classic']],
            ['Tweed Field Coat', 'men', 'outerwear', 259, 299, $top, ['Brown'], true, ['1505022610485-0249ba5b3675'], ['classic', 'smart-casual']],
            ['Leather Biker Jacket', 'men', 'outerwear', 239, null, $top, ['Cognac'], false, ['1487222477894-8943e31ef7b2'], ['street', 'statement']],
            ['Merino Crew Knit', 'men', 'knitwear', 69, null, $top, ['Burgundy', 'Black'], false, ['1506634572416-48cdfe530110'], ['smart-casual', 'modern']],
            ['Suede Overshirt', 'men', 'outerwear', 129, null, $top, ['Tan'], false, ['1617114919297-3c8ddb01f599'], ['smart-casual', 'street']],
            ['Double Monk Strap', 'men', 'shoes', 139, null, $shoe, ['Brown'], true, ['1533867617858-e7b97e060509', '1614252235316-8c857d38b5f4'], ['classic', 'evening']],
            ['Check Tailored Blazer', 'women', 'blazers', 169, null, $women, ['Green check'], true, ['1485968579580-b6d095142e6e'], ['modern', 'smart-casual', 'statement']],
            ['Red Maxi Dress', 'women', 'dresses', 119, 149, $women, ['Red'], true, ['1595777457583-95e059d581b8'], ['evening', 'statement']],
            ['Wide-Leg Trouser', 'women', 'trousers', 85, null, $women, ['Sand', 'Black'], false, ['1614251055880-ee96e4803393'], ['modern', 'smart-casual']],
            ['Wool Wrap Coat', 'women', 'outerwear', 199, null, $women, ['Burgundy'], true, ['1483985988355-763728e1935b'], ['classic', 'modern']],
            ['Sheer Black Blouse', 'women', 'dresses', 95, null, $women, ['Black'], false, ['1524504388940-b1c1722653e1'], ['evening', 'modern']],
            ['Sequin Two-Piece', 'women', 'dresses', 159, null, $women, ['Ruby'], false, ['1550614000-4895a10e1bfd'], ['evening', 'statement']],
            ['Floral Day Dress', 'women', 'dresses', 89, null, $women, ['Red floral'], false, ['1572804013309-59a88b7e92f1'], ['smart-casual', 'street']],
        ];

        foreach ($rows as $i => [$name, $gender, $categorySlug, $price, $compare, $sizes, $colors, $featured, $photos, $styles]) {
            $product = Product::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $desc,
                    'gender' => $gender,
                    'category_id' => Category::where('slug', $categorySlug)->value('id'),
                    'price' => $price,
                    'compare_at_price' => $compare,
                    'images' => array_map(fn ($p) => "/stock/{$p}.jpg", $photos),
                    'styles' => $styles,
                    'is_active' => true,
                    'is_featured' => $featured,
                    'created_at' => now()->subDays($i), // newest first, like the demo data
                ],
            );

            foreach ($colors as $ci => $color) {
                foreach ($sizes as $si => $size) {
                    ProductVariant::updateOrCreate(
                        ['product_id' => $product->id, 'size' => $size, 'color' => $color],
                        [
                            'stock' => (($si * 3 + $ci * 5) % 7 === 0) ? 0 : 3 + (($si + $ci) % 6),
                            'sort' => $ci * 10 + $si,
                        ],
                    );
                }
            }
        }
    }
}
