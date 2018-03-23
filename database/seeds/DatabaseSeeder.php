<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

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

        $this->call(PermissionsTableSeeder::class);
        $this->call(RolesTableSeeder::class);
        $this->call(ConnectRelationshipsSeeder::class);
        $this->call(ThemesTableSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(BotAccountsSeeder::class);
        $this->call(DaysTableSeeder::class);

        # tests
        $this->call(GroupTypesSeeder::class);
        $this->call(SubscriptionsTypesSeeder::class);

        Model::reguard();
    }
}
