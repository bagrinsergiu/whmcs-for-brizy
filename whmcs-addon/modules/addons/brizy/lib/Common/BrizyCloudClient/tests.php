<?php
require __DIR__ . '/vendor/autoload.php';

use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Client;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces\ListWorkspaceRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces\CreateWorkspaceRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces\CountWorkspaceRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces\DeleteWorkspaceRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces\UpdateWorkspaceRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users\ListUserRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users\GetUserRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users\CreateUserRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users\DeleteUserRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users\UpdateUserRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\ListProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\CreateProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\ListTeamMemberRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\CreateTeamMemberRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\UpdateTeamMemberRequest;

$client = new Client(
    '70ef6350375cd00e253c905e3ae9a07e097e990c489860e449215c35f349efde', // 7c0825d3ae967f526a6561aec95374027e3dfff8851a7d1fb113574eeda2ab71
    '396003', // ?
    'https://admin.mysitebuilder.online/', // https://admin.brizy.io/
    'https://admin.mysitebuilder.online/api/2.0/', // https://admin.brizy.io/api/2.0/
);

// List Workspaces
// $ws = $client->workspaces->list(new ListWorkspaceRequest());
// echo '<pre>';
// print_r($ws);
// echo '</pre>';
// die();

// Create Workspace
// $ws = $client->workspaces->create(new CreateWorkspaceRequest(['name' => 'Dotinum test workspace from API']));
// echo '<pre>';
// print_r($ws);
// echo '</pre>';
// die();

// Count Workspace
// $ws = $client->workspaces->count(new CountWorkspaceRequest('admin'));
// echo '<pre>';
// print_r($ws);
// echo '</pre>';
// die();

// Delete Workspace
// $ws = $client->workspaces->delete(new DeleteWorkspaceRequest(22423625));
// echo '<pre>';
// print_r($ws);
// echo '</pre>';
// die();

// Update Workspace
// $ws = $client->workspaces->update(new UpdateWorkspaceRequest(22419110, ['name' => 'New name - Dotinum test workspace from API']));
// echo '<pre>';
// print_r($ws);
// echo '</pre>';
// die();

// List Users
// $ws = $client->users->list(new ListUserRequest(1, 200));
// echo '<pre>';
// print_r($ws);
// echo '</pre>';
// die();

// Get User
// $ws = $client->users->get(new ListUserRequest(null));
// echo '<pre>';
// print_r($ws);
// echo '</pre>';
// die();

// Create User
// $ws = $client->users->create(new CreateUserRequest([
//     'email' => 'serwisy.kubera@gmail.com',
//     'status' => 'verified'
// ]));
// echo '<pre>';
// print_r($ws);
// echo '</pre>';
// die();

// Delete User
// $ws = $client->users->delete(new DeleteUserRequest(22419110));
// echo '<pre>';
// print_r($ws);
// echo '</pre>';
// die();

// Update User
// $ws = $client->users->update(new UpdateUserRequest(22419110, ['name' => 'qwe new name - test workspace from api']));
// echo '<pre>';
// print_r($ws);
// echo '</pre>';
// die();

// List Projects
// $ws = $client->projects->list(new ListProjectRequest(22423576));
// echo '<pre>';
// print_r($ws);
// echo '</pre>';
// die();

// Create Project
// $ws = $client->projects->create(new CreateProjectRequest([
//     'name' => 'Dotinum test project from API',
//     'workspace' => '22423576'
// ]));
// echo '<pre>';
// print_r($ws);
// echo '</pre>';

// List Team Members
// $ws = $client->teamMembers->list(new ListTeamMemberRequest(22423576));
// echo '<pre>';
// print_r($ws);
// echo '</pre>';
// die();

// Create Team Member
// $ws = $client->teamMembers->create(new CreateTeamMemberRequest([
//     'user' => '2967933',
//     'workspace' => '22423576',
//     'role' => 'manager'
// ]));
// echo '<pre>';
// print_r($ws);
// echo '</pre>';
// die();

// Update Team Member
// $ws = $client->teamMembers->update(new UpdateTeamMemberRequest(4094351, [
//     'status' => 'approved'
// ]));
// echo '<pre>';
// print_r($ws);
// echo '</pre>';
// die();

// SSO
// $ws = $client->sso->getRedirectUrl(22423637, ['email' => 'serwisy.kubera@gmail.com']);
// echo '<pre>';
// print_r($ws);
// echo '</pre>';
// die();
