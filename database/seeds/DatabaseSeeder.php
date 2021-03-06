<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $this->call('CountriesTableSeeder');
        $this->call('UsersTableSeeder');
        $this->call('ItemTypesSeeder');
        $this->call('MeasureTypesSeeder');
        $this->call('StyleSeeder');
        $this->call('CategoriesSeeder');
        $this->call('DataProvidersSeeder');
        $this->call('EpiSeeder');
        $this->call('EpiDataSeeder');
        $this->call('PoiTypesSeeder');
        $this->call('BasemapTableSeeder');
        Model::reguard();
    }
}
