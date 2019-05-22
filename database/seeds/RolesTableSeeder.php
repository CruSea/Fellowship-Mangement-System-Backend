<?php

use Illuminate\Database\Seeder;
use App\Role;
class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $super_admin_role = new Role();
        $super_admin_role::insert([
            'name' => 'super admin',
            'description' => 'controls everything in the system'
        ]);
        $admin_role = new Role();
        $admin_role::insert([
            'name' => 'admin',
            'description' => 'admin oversee and manage the editer role'
        ]);
        $editer_role = new Role();
        $editer_role::insert([
            'name' => 'editer',
            'description' => 'editing some tasks'
        ]);
        $viewer_role = new Role();
        $viewer_role::insert([
            'name' => 'viewer',
            'description' => 'viewing news feed'
        ]); 
    }
}
