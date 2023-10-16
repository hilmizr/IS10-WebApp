<?php

namespace Database\Seeders;

use App\Models\CompanyUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jobs = [];
        for ($i = 0; $i < 10; $i++) {
            $jobs[] = [
                'title' => fake()->jobTitle(),
                'requirements' => fake()->text(),
                'description' => fake()->text(),
                'salary' => fake()->numberBetween(1000000, 10000000),
                'location' => fake()->address(),
                'company_user_id' => CompanyUser::all()->random()->id,
            ];
        }

        for ($i = 0; $i < 10; $i++) {
            \App\Models\Job::create($jobs[$i]);
        }
    }
}
