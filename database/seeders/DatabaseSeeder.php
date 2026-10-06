<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // jaunai instalācijai vajadzīgas tikai lomas – skolotāji reģistrējas paši, studentus pievieno skolotājs
    public function run(): void
    {
        $this->call(RoleSeeder::class);
    }
}
