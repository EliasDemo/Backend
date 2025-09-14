<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call(PermissionSeeder::class);

        $this->call(UserSeeder::class);


        $this->call([UniversidadSeeder::class,]);

        $this->call(class: [SedeSeeder::class,]);

        $this->call(class: [FacultadSeeder::class,]);

        $this->call(class: [EscuelaProfesionalSeeder::class,]);

        $this->call(class: [EpSedeSeeder::class,]);

        $this->call([PeriodoAcademicoSeeder::class,]);
    }
}
