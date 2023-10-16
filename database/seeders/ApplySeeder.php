<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $applies = [];
        for ($i = 0; $i < 100; $i++) {
            $applies[] = [
                'user_id' => \App\Models\User::all()->random()->id,
                'job_id' => \App\Models\Job::all()->random()->id,
            ];
        }

        for ($i = 0; $i < 100; $i++) {
            DB::table('job_user')->insert($applies[$i]);
        }
    }
}
