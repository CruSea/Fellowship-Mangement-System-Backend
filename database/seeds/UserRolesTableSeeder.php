<?php

use Illuminate\Database\Seeder;
use App\UserRole;
class UserRolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $superAdminUser = new UserRole();
        $superAdminUser::insert([
            'user_id' => 1,
            'role_id' => 1,
        ]);
    }
}
