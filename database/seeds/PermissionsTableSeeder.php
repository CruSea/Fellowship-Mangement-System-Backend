<?php

use Illuminate\Database\Seeder;
use App\Permission;
class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $create_user = new Permission();
        $create_user::insert([
            'name' => 'create',
            'description' => 'creating a new user',
        ]);
        $edit_user = new Permission();
        $edit_user::insert([
            'name' => 'edit',
            'description' => 'updating a user',
        ]);
        $edit_password = new Permission();
        $edit_password::insert([
            'name' => 'edit_password',
            'description' => 'editing password',
        ]);
        $delete_user = new Permission();
        $delete_user::insert([
            'name' => 'delete_user',
            'description' => 'deleting user',
        ]);
        $edit_status = new Permission();
        $edit_status::insert([
            'name' => 'edit_status',
            'description' => 'edit user status',
        ]);
        $edit_role = new Permission();
        $edit_role::insert([
            'name' => 'edit_role',
            'description' => 'edit user role',
        ]);
        
    }
}
