<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\User;
use Hamcrest\Core\HasToString;
use Illuminate\Container\Attributes\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Starting NIM
        $startingNim = 2241720000;
        $totalStudents = 378;
        $studentsPerClass = $totalStudents/2;
        $totalClasses = (int) ceil($totalStudents / $studentsPerClass); // Hitung jumlah kelas

        $currentNim = $startingNim;

        for ($class = 1; $class <= $totalClasses; $class++) {
            // Tentukan jumlah mahasiswa untuk kelas ini
            $studentsInClass = ($class == $totalClasses) ? $totalStudents - ($studentsPerClass * ($totalClasses - 1)) : $studentsPerClass;

            for ($i = 1; $i < $studentsInClass; $i++) {
                // Buat mahasiswa
                User::create([
                    'username'   => $currentNim,
                    'password'   => Hash::make($currentNim),
                    'email'      => fake()->unique()->safeEmail(),
                    'nim'        => $currentNim,
                    'name'       => fake()->name(),
                    'birth_date' => now()->subYears(20),
                    'gender'     => fake()->randomElement(['Male', 'Female']),
                    'group_id'   => $class,  // Asumsikan group_id berdasarkan kelas
                    'verified'   => false,
                ]);

                // Inkrementasi NIM untuk mahasiswa berikutnya
                $currentNim++;
            }

            // Jika jumlah mahasiswa sudah mencapai totalStudents, keluar dari loop
            if ($currentNim - $startingNim >= $totalStudents) {
                break;
            }
        }

        User::where('username', '2241720083')->update(['name' => 'Atiqah Nurul Asri', 'gender' => 'Female', 'email' => 'xplore1.pbl@gmail.com']);
        User::where('username', '2241720084')->update(['name' => 'Amalia Agung Septarina', 'gender' => 'Female', 'email' => 'xplore2.pbl@gmail.com']);
        User::where('username', '2241720085')->update(['name' => 'Dian Hanifudin Subhi', 'gender' => 'Male', 'email' => 'xplore3.pbl@gmail.com']);
        User::where('username', '2241720086')->update(['name' => 'Rosa Andrie Asmara', 'gender' => 'Male', 'email' => 'xplore4.pbl@gmail.com']);
        User::where('username', '2241720087')->update(['name' => 'Yuri Ariyanto', 'gender' => 'Male', 'email' => 'xplore5.pbl@gmail.com']);
        
        User::where('username', '2241720207')->update(['name' => 'Ahmad Taufiq Hidayatulloh', 'gender' => 'Male', 'email' => 'hidayatullohtaufiq0@gmail.com']);
        User::where('username', '2241720168')->update(['name' => 'Lucky Kurniawan Langoday', 'gender' => 'Male', 'email' => 'luckykurniawan656@gmail.com']);
        User::where('username', '2241720036')->update(['name' => 'Putri Norchasana', 'gender' => 'Female','email'=>'putrinorchasana@gmail.com']);
        User::where('username', '2241720082')->update(['name' => 'Raffy Jamil Octavialdy', 'gender' => 'Male', 'email' => 'rafiteguh6@gmail.com']);
    }
}
