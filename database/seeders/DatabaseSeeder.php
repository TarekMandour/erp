<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\ClothesShop\PostingRuleSeeder;
use Database\Seeders\ClothesShop\PostingScenarioSeeder;
use Illuminate\Database\Seeder;
use Database\Seeders\ClothesShop\ClothesShopSeeder;
use Database\Seeders\ClothesShop\AccountTreeSeeder;
use Database\Seeders\FoodShop\FoodShopSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'phone' => '123456987',
            'email' => 'test@example.com',
        ]);

        $this->call([
            AdminSeeder::class,
            SettingSeeder::class,
            ClothesShopSeeder::class,
            AccountTreeSeeder::class,
            PostingScenarioSeeder::class,
            PostingRuleSeeder::class,
            // FoodShopSeeder::class,
        ]);
    }
}
