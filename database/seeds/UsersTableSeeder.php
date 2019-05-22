<?php

use Illuminate\Database\Seeder;
use App\User;
use App\Role;
use App\Permission;
class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       $superAdminRole = new Role();
        $superAdminRole->name         = 'super-admin';
        $superAdminRole->display_name = 'Super Admin'; // optional
        $superAdminRole->description  = 'User is super admin of a given project'; // optional
        $superAdminRole->save();

        $adminRole = new Role();
        $adminRole->name         = 'admin';
        $adminRole->display_name = 'User Administrator'; // optional
        $adminRole->description  = 'User is allowed to manage and edit other users'; // optional
        $adminRole->save();

        $editerRole = new Role();
        $editerRole->name         = 'editer';
        $editerRole->display_name = 'Editer'; // optional
        $editerRole->description  = 'User is editer of a given project'; // optional
        $editerRole->save();

        $viewerRole = new Role();
        $viewerRole->name         = 'viewer';
        $viewerRole->display_name = 'Viewer'; // optional
        $viewerRole->description  = 'User has only viewing role in this project'; // optional
        $viewerRole->save();

        $createUser = new Permission();
        $createUser->name         = 'create-user';
        $createUser->display_name = 'Create Users'; // optional
        // Allow a user to...
        $createUser->description  = 'create new users'; // optional
        $createUser->save();

        $editUser = new Permission();
        $editUser->name         = 'edit-user';
        $editUser->display_name = 'Edit Users'; // optional
        // Allow a user to...
        $editUser->description  = 'edit existing users'; // optional
        $editUser->save();

        $deleteUser = new Permission();
        $deleteUser->name           = 'delete-user';
        $deleteUser->display_name   = 'Delete User';    // optional
        $deleteUser->description    = 'delete existing user';  // optional
        $deleteUser->save();

        $editUserStatus = new Permission();
        $editUserStatus->name           = 'edit-user-status';
        $editUserStatus->display_name   = 'Edit User Status';   //optional
        $editUserStatus->description    = 'Edit existing user status';  //optional
        $editUserStatus->save();

        $editUserRole = new Permission();
        $editUserRole->name             = 'edit-user-role';
        $editUserRole->display_name     = 'Edit User Role'; // optional
        $editUserRole->description      = 'Edit existing user role'; // optional
        $editUserRole->save();

        $editOwnPassword = new Permission();
        $editOwnPassword->name         = 'edit-user-password';
        $editOwnPassword->display_name = 'Edit User Password';  //optional
        $editOwnPassword->description  = 'Edit existing user password'; // optional
        $editOwnPassword->save();

        $createContact = new Permission();
        $createContact->name            = 'create-contact';
        $createContact->display_name    = 'create contact';
        $createContact->description     = 'Createing a new contact';
        $createContact->save();

        $editContact = new Permission();
        $editContact->name         = 'edit-contact';
        $editContact->display_name = 'Edit Contact';  //optional
        $editContact->description  = 'Edit existing contact'; // optional
        $editContact->save();

        $deleteContact = new Permission();
        $deleteContact->name           = 'delete-contact';
        $deleteContact->display_name   = 'Delete Contact';    // optional
        $deleteContact->description    = 'delete existing contact';  // optional
        $deleteContact->save();

        $getContact = new Permission();
        $getContact->name           = 'get-contact';
        $getContact->display_name   = 'Get Contact';    // optional
        $getContact->description    = 'get existing contact';  // optional
        $getContact->save();

        $createTeam = new Permission();
        $createTeam->name           = 'create-team';
        $createTeam->display_name   = 'create team';
        $createTeam->description    = 'create a new Team';
        $createTeam->save();

        $getTeam = new Permission();
        $getTeam->name           = 'get-team';
        $getTeam->display_name   = 'Get Team';    // optional
        $getTeam->description    = 'get existing team';  // optional
        $getTeam->save();

        $editTeam = new Permission();
        $editTeam->name         = 'edit-team';
        $editTeam->display_name = 'Edit Team';  //optional
        $editTeam->description  = 'Edit existing team'; // optional
        $editTeam->save();

        $deleteTeam = new Permission();
        $deleteTeam->name           = 'delete-team';
        $deleteTeam->display_name   = 'Delete Team';    // optional
        $deleteTeam->description    = 'delete existing team';  // optional
        $deleteTeam->save();

        // this includes deleting members, editing members, seeing members and adding members to the team.
        $manageTeamMembers = new Permission();
        $manageTeamMembers->name           = 'manage-members';
        $manageTeamMembers->display_name   = 'manage team members';
        $manageTeamMembers->description    = 'manage members in the team';
        $manageTeamMembers->save();


        $superAdminRole->attachPermissions(
            array(
                $createUser, 
                $editUser,
                $deleteUser,
                $editUserStatus,
                $editUserRole,
                $editOwnPassword,

                $createContact,
                $editContact,
                $getContact,
                $deleteContact,
                
                $createTeam,
                $getTeam,
                $editTeam,
                $deleteTeam,
                
                $manageTeamMembers,
            ));
        // equivalent to $superAdmin->perms()->sync(array($createUser->id, $editUser->id));

        $adminRole->attachPermissions(
            array(
                $createUser,
                $editUser,
                $editOwnPassword,
                $deleteUser,

                $createContact,
                $editContact,
                $getContact,
                $deleteContact,

                $createTeam,
                $getTeam,
                $editTeam,
                $deleteTeam,
                
                $manageTeamMembers,

            ));
        $editerRole->attachPermissions(
            array(
                $editOwnPassword,
            ));
        $viewerRole->attachPermissions(
            array(
                $editOwnPassword
            ));
        // equivalent to $admin->perms()->sync(array($createUser->id));
    }
}
