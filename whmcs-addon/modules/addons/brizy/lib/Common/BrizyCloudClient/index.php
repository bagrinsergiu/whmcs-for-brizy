<?php


use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\BrizyClient;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces\CreateWorkspaceRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\CreateProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users\CreateUserRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\CreateTeamMemberRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\UpdateTeamMemberRequest;

$client = new BrizyClient(
    '70ef6350375cd00e253c905e3ae9a07e097e990c489860e449215c35f349efde', // 7c0825d3ae967f526a6561aec95374027e3dfff8851a7d1fb113574eeda2ab71
    '396003', // ?
    'https://admin.mysitebuilder.online/', // https://admin.brizy.io/
    'https://admin.mysitebuilder.online/api/2.0/', // https://admin.brizy.io/api/2.0/
);

$workspaceName = 'Dotinum test workspace from API';
$projectName = 'Dotinum test project from API';
$userEmail = 'serwisy.kubera@gmail.com';

// Step 1. Create a Workspace
try {
    $workspace = $client->workspaces->create(new CreateWorkspaceRequest([
        'name' => $workspaceName
    ]));
} catch (\Throwable $e) {
    echo '<pre>';
    print_r($e->getMessage());
    echo '</pre>';
}

// Step 2. Create a Project
try {
    $project = $client->projects->create(new CreateProjectRequest([
        'name' => $projectName,
        'workspace' => (string) $workspace->getId()
    ]));
} catch (\Throwable $e) {
    echo '<pre>';
    print_r($e->getMessage());
    echo '</pre>';
}

// Step 3. Create a User
try {
    $user = $client->users->create(new CreateUserRequest([
        'email' => $userEmail,
        'status' => 'verified'
    ]));
} catch (\Throwable $e) {
    echo '<pre>';
    print_r($e->getMessage());
    echo '</pre>';
}

// Step 4.1. Invite a User to the Workspace as a team member and assign them the Manager role
try {
    $teamMember = $client->teamMembers->create(new CreateTeamMemberRequest([
        'user' => (string) $user->getId(),
        'workspace' => (string) $workspace->getId(),
        'role' => 'manager'
    ]));
} catch (\Throwable $e) {
    echo '<pre>';
    print_r($e->getMessage());
    echo '</pre>';
}

// Step 4.2. Change User status to approved
try {
    $teamMember = $client->teamMembers->update(new UpdateTeamMemberRequest($teamMember->getId(), [
        'status' => 'approved'
    ]));
} catch (\Throwable $e) {
    echo '<pre>';
    print_r($e->getMessage());
    echo '</pre>';
}

// Step 5. Authorize the Project.uid via SSO
try {
    $redirectUrl = $client->sso->getRedirectUrl($project->getId(), ['email' => $userEmail]);

    header('Location: ' . $redirectUrl);
    exit;
} catch (\Throwable $e) {
    echo '<pre>';
    print_r($e->getMessage());
    echo '</pre>';
}
