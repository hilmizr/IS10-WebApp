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
        $uniqueCombinations = []; // To keep track of unique combinations

        for ($i = 0; $i < 100; $i++) {
            $userId = \App\Models\User::all()->random()->id;
            $jobId = \App\Models\Job::all()->random()->id;
            $combination = "{$userId}-{$jobId}";

            // Check if combination is already generated
            while (in_array($combination, $uniqueCombinations)) {
                $userId = \App\Models\User::all()->random()->id;
                $jobId = \App\Models\Job::all()->random()->id;
                $combination = "{$userId}-{$jobId}";
            }

            $uniqueCombinations[] = $combination;

            $applies[] = [
                'user_id' => $userId,
                'job_id' => $jobId,
            ];
        }

        for ($i = 0; $i < 100; $i++) {
            if (isset($applies[$i])) {
                DB::table('job_user')->insert([
                    'job_id' => $applies[$i]['job_id'],
                    'user_id' => $applies[$i]['user_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
