<?php

use Illuminate\Database\Seeder;
use App\PermissionRole;
class PermissionRolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /***************************************************************
        * super_admin permission role
        ****************************************************************/
        $superAdminCreateUser = new PermissionRole();
        $superAdminCreateUser::insert([
            'role_id' => 1,
            'permission_id' => 1,
        ]);
        $superAdminEditItself = new PermissionRole();
        $superAdminEditItself::insert([
            'role_id' => 1,
            'permission_id' => 2,
        ]);
        $superAdminEditPassword = new PermissionRole();
        $superAdminEditPassword::insert([
            'role_id' => 1,
            'permission_id' => 3,
        ]);
        $superAdminDeleteUser = new PermissionRole();
        $superAdminDeleteUser::insert([
            'role_id' => 1,
            'permission_id' => 4,
        ]);
        $superAdminEditStatus = new PermissionRole();
        $superAdminEditStatus::insert([
            'role_id' => 1,
            'permission_id' => 5,
        ]);
        $superAdminEditRole = new PermissionRole();
        $superAdminEditRole::insert([
            'role_id' => 1,
            'permission_id' => 6,
        ]);
        /***************************************************************
        * admin permission role
        ****************************************************************/
        $adminEditItself = new PermissionRole();
        $adminEditItself::insert([
            'role_id' => 2,
            'permission_id' => 2,
        ]);
        $adminEditPassword = new PermissionRole();
        $adminEditPassword::insert([
            'role_id' => 2,
            'permission_id' => 3,
        ]);
        $adminEditStatus = new PermissionRole();
        $adminEditStatus::insert([
            'role_id' => 2,
            'permission_id' => 5,
        ]);
        /***************************************************************
        * editer permission role
        ****************************************************************/
        $editerEditItself = new PermissionRole();
        $editerEditItself::insert([
            'role_id' => 3,
            'permission_id' => 2,
        ]);
        /***************************************************************
        * viewer permission role
        ****************************************************************/
        $viewerEditItself = new PermissionRole();
        $viewerEditItself::insert([
            'role_id' => 4,
            'permission_id' => 2,
        ]);
    }
}
