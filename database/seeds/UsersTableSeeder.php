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
        $superAdminRole->description  = 'User is super admin of a fellowship management system'; // optional
        $superAdminRole->save();

        $ownerRole = new Role();
        $ownerRole->name           = 'owner';
        $ownerRole->display_name   = 'Owner';
        $ownerRole->description    = 'User is owner of a given project';
        $ownerRole->save();

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

        $getUser = new Permission();
        $getUser->name          = 'get-user';
        $getUser->display_name  = 'get user';
        $getUser->description   = 'get user';
        $getUser->save();

        $getMe = new Permission();
        $getMe->name            = 'get-me';
        $getMe->display_name    = 'get me';
        $getMe->description     = 'user gets its own account';
        $getMe->save();

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


        // message permission
        $sendMessage = new Permission();
        $sendMessage->name                 = 'send-message';
        $sendMessage->display_name         = 'send message';
        $sendMessage->description          = 'send member message';
        $sendMessage->save();

        $getMessage = new Permission();
        $getMessage->name            = 'get-message';
        $getMessage->display_name    = 'get message';
        $getMessage->description     = 'get sent message and respond message from users';
        $getMessage->save();

        $deleteContactMessage = new Permission();
        $deleteContactMessage->name               = 'delete-contact-message';
        $deleteContactMessage->display_name       = 'delete contact message';
        $deleteContactMessage->description        = 'delete contact message';
        $deleteContactMessage->save();


        // negarit_api permission
        $storeSmsPort = new Permission();
        $storeSmsPort->name             = 'store-sms-port';
        $storeSmsPort->display_name     = 'store sms port';
        $storeSmsPort->description      = 'store sms port from negarit api';
        $storeSmsPort->save();

        $getSmsPort = new Permission();
        $getSmsPort->name               = 'get-sms-port';
        $getSmsPort->display_name       = 'get sms port';
        $getSmsPort->description        = 'get sms port';
        $getSmsPort->save();

        $updateSmsPort = new Permission();
        $updateSmsPort->name            = 'update-sms-port';
        $updateSmsPort->display_name    = 'update sms port';
        $updateSmsPort->description     = 'update sms port';
        $updateSmsPort->save();

        $deleteSmsPort = new Permission();
        $deleteSmsPort->name            = 'delete-sms-port';
        $deleteSmsPort->display_name    = 'delete sms port';
        $deleteSmsPort->description     = 'delete sms port';
        $deleteSmsPort->save();


        // setting permission
        $createSetting = new Permission();
        $createSetting->name            = 'create-setting';
        $createSetting->display_name    = 'create setting';
        $createSetting->description     = 'create setting';
        $createSetting->save();

        $getSetting = new Permission();
        $getSetting->name               = 'get-setting';
        $getSetting->display_name       = 'get setting';
        $getSetting->description        = 'get setting';
        $getSetting->save();

        $updateSetting = new Permission();
        $updateSetting->name            = 'update-setting';
        $updateSetting->display_name    = 'update setting';
        $updateSetting->description     = 'update setting';
        $updateSetting->save();

        $deleteSetting = new Permission();
        $deleteSetting->name            = 'delete-setting';
        $deleteSetting->display_name    = 'delete setting';
        $deleteSetting->description     = 'delete setting';
        $deleteSetting->save();



        $superAdminRole->attachPermissions(
            array(
                $createUser, 
                $editUser,
                $deleteUser,
                $editUserStatus,
                $editUserRole,
                $editOwnPassword,
                $getUser,
                $getMe,

                $createContact,
                $editContact,
                $getContact,
                $deleteContact,
                
                $createTeam,
                $getTeam,
                $editTeam,
                $deleteTeam,
                
                $manageTeamMembers,

                $createSetting,
                $getSetting,
                $updateSetting,
                $deleteSetting,

                $storeSmsPort,
                $getSmsPort,
                $updateSmsPort,
                $deleteSmsPort,

                $sendMessage,
                $getMessage,
                $deleteContactMessage,


            ));
        // equivalent to $superAdmin->perms()->sync(array($createUser->id, $editUser->id));

        $ownerRole->attachPermissions(
            array(
                $createUser, 
                $editUser,
                $deleteUser,
                $editUserStatus,
                $editUserRole,
                $editOwnPassword,
                $getUser,
                $getMe,

                $createContact,
                $editContact,
                $getContact,
                $deleteContact,
                
                $createTeam,
                $getTeam,
                $editTeam,
                $deleteTeam,
                
                $manageTeamMembers,

                $createSetting,
                $getSetting,
                $updateSetting,
                $deleteSetting,

                $storeSmsPort,
                $getSmsPort,
                $updateSmsPort,
                $deleteSmsPort,

                $sendMessage,
                $getMessage,
                $deleteContactMessage,
            )
        );
        $adminRole->attachPermissions(
            array(
                $createUser,
                $editUser,
                $editOwnPassword,
                $deleteUser,
                $editUserStatus,
                $getUser,
                $getMe,

                $createContact,
                $editContact,
                $getContact,
                $deleteContact,

                $createTeam,
                $getTeam,
                $editTeam,
                $deleteTeam,
                
                $manageTeamMembers,

                $createSetting,
                $getSetting,
                $updateSetting,
                $deleteSetting,

                $storeSmsPort,
                $getSmsPort,
                $updateSmsPort,
                $deleteSmsPort,

                $sendMessage,
                $getMessage,
                $deleteContactMessage

            ));
        $editerRole->attachPermissions(
            array(
                $editOwnPassword,
                $getMe,

                $createSetting,
                $getSetting,
                $updateSetting,
                $deleteSetting,

                $storeSmsPort,
                $getSmsPort,
                $updateSmsPort,
                $deleteSmsPort,

                $sendMessage,
                $getMessage,
                $deleteContactMessage
            ));
        $viewerRole->attachPermissions(
            array(
                $editOwnPassword,
                $getMe,

                $getSetting,

                $getSmsPort,

                $getMessage,
            ));
        // equivalent to $admin->perms()->sync(array($createUser->id));
    }
}
