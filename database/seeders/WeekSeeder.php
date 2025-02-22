<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WeekSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $startDate = Carbon::create(2025, 2, 24); // Tanggal mulai dari minggu pertama (21 Oktober 2024)
        $weeks = [];

        for ($i = 1; $i <= 17; $i++) {
            $weeks[] = [
                'name' => (string)$i,
                'start_date' => $startDate->format('Y-m-d'), // Senin
                'end_date' => $startDate->copy()->addDays(6)->format('Y-m-d'), // Minggu
            ];

            $startDate->addWeek();
        }
        DB::table('weeks')->insert($weeks);
    }
}
