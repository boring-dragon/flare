<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RolesAndPermissions::class,
            CreateRaces::class,
            CreateClasses::class,
            CreateItemsSeeder::class,
            CreateMonstersSeeder::class,
        ]);
    }
}
