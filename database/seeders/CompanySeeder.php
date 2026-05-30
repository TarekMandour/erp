<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\CompanyUser;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::create([
            'name' => 'Example Company',
            'email' => 'company@example.com',
            'phone' => '1234567890',
        ]);

        CompanyUser::create([
            'company_id' => $company->id,
            'name' => 'Company Admin',
            'phone' => '201006287379',
            'email' => 'user1@company.com',
            'password' => bcrypt('password'),
            'type' => 'super',
            'is_active' => '1'
        ]);
    }
}
