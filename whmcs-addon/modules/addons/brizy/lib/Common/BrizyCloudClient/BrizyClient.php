<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient;

use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Http\HttpClient;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Sso\SsoManager;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Service\WorkspaceService;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Service\ProjectService;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Service\UserService;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Service\TeamMemberService;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Service\SimpleService;

use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces\CreateWorkspaceRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces\ListWorkspaceRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\CreateProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\ListProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\DeleteProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users\CreateUserRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\CreateTeamMemberRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\UpdateTeamMemberRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users\ListUserRequest;

use WHMCS\Module\Addon\Brizy\Common\Settings;
use WHMCS\Service\Service;
use WHMCS\Database\Capsule;


class BrizyClient
{
    public WorkspaceService $workspaces;
    public ProjectService $projects;
    public UserService $users;
    public TeamMemberService $teamMembers;
    public SsoManager $sso;
    public HttpClient $http;

    public function __construct(string $secretKey, string $partnerId, string $whiteLabelDomain, string $baseUri)
    {
        $http = new HttpClient($secretKey, $baseUri);

        $this->http = $http;
        $this->workspaces = new WorkspaceService($http);
        $this->projects = new ProjectService($http);
        $this->users = new UserService($http);
        $this->teamMembers = new TeamMemberService($http);

        $this->sso = new SsoManager($partnerId, $secretKey, $whiteLabelDomain);
    }


    public static function deleteWorkspace($workspaceId) {


        $cloudApiKey = Settings::get('cloud_access_key');
        $publicPartnerId = Settings::get('cloud_public_partner_id');
        $wlDashboardPath = rtrim(Settings::get('cloud_wl_dashboard_domain'), '/') . '/';
        $baseUri = $wlDashboardPath . 'api/2.0/';

        $client = new self(
            $cloudApiKey,
            $publicPartnerId,
            $wlDashboardPath,
            $baseUri
        );

        try {
            $apiResponseProjects = $client->projects->list(new ListProjectRequest($workspaceId));
            logActivity('[BizyCloud]: #1 DELETING WORKSPACE ID: '.$workspaceId.'', 0);
        } catch (\Throwable $e) {
            logActivity('![BizyCloud]: #1 DELETING WORKSPACE ID: '.$workspaceId.' PROJECTS:'.$e->getMessage(), 0);

            return false;
        }

        foreach($apiResponseProjects->toArray() as $project){

            try {
                $response = $client->projects->delete(new DeleteProjectRequest($project['id']));
                logActivity('[BizyCloud]: #2 DELETING WORKSPACE ID: '.$workspaceId.' project deleted: '.$project['id'], 0);
            } catch (\Throwable $e) {
                logActivity('![BizyCloud]: #2 DELETING WORKSPACE ID: '.$workspaceId.' - cant delete the project id: '.$project['id'].'  :'.$e->getMessage(), 0);
            }
        }


        try {
            $deleteWorkspaceRequest = $client->http->request('DELETE','workspaces/'.$workspaceId);
            logActivity('[BizyCloud]: #3 DELETING WORKSPACE ID: '.$workspaceId.' workspace deleted!', 0);
        } catch (\Throwable $e) {
            logActivity('![BizyCloud]: ERROR - #3 DELETING WORKSPACE ID: '.$workspaceId.' cant delete workspace :'.$e->getMessage(), 0);
            return false;
        }

        return true;
    }

    public static function autoCreateWorkspace($serviceId) {

        $service = Service::where('id', $serviceId)->first();

        if (!$service) {
            logActivity('![BizyCloud]: #1 (service id: '.$serviceId.') - service not found', 0);
            return;
        }

        $cloudApiKey = Settings::get('cloud_access_key');
        $publicPartnerId = Settings::get('cloud_public_partner_id');
        $wlDashboardPath = rtrim(Settings::get('cloud_wl_dashboard_domain'), '/') . '/';
        $baseUri = $wlDashboardPath . 'api/2.0/';

        $client = new self(
            $cloudApiKey,
            $publicPartnerId,
            $wlDashboardPath,
            $baseUri
        );

        $workspaceName = 'Workspace: '.$service->client->email;
        $projectName = 'First project';

        $userEmail = $service->serviceProperties->get(Settings::$servicePropertiesWorkspaceCoreTeamMember);
        if (!$userEmail) {
            $userEmail = $service->client->email;
        }

        $workspaceCheck = $service->serviceProperties->get([Settings::$servicePropertiesWorkspace]);

        if ($workspaceCheck) {
            logActivity('[BizyCloud]: #0 (service id: '.$serviceId.') workspace exists in DB, workspace ID '.$workspaceCheck, 0);
            return;
        }

        $service->serviceProperties->save([Settings::$servicePropertiesWorkspace => '']);

        // Step 1. Create a Workspace
        try {
            $workspace = $client->workspaces->create(new CreateWorkspaceRequest([
                'name' => $workspaceName
            ]));

            logActivity('[BizyCloud]: #1 (service id: '.$serviceId.') workspace created: '.$workspaceName . ' (ID: '. (string) $workspace->getId().')', 0);

        } catch (\Throwable $e) {
            logActivity('![BizyCloud]: #1 (service id: '.$serviceId.') there was a problem when creating a workspace '.$workspaceName .': '.$e->getMessage() , 0);
        }

        if ($workspace && $workspace->getId()) {
            $service->serviceProperties->save([Settings::$servicePropertiesWorkspace => $workspace->getId()]);
            $service->serviceProperties->save([Settings::$servicePropertiesWorkspaceCoreTeamMember => $userEmail]);
        }

        // Step 2. Create a Project
        $templateProjectId = $service->serviceProperties->get([Settings::$servicePropertiesTemplate]);
        if ($templateProjectId) {

            try {
                $duplicateProjectResponse = $client->http->request('POST','projects/'.$templateProjectId.'/duplicates', [
                    'json' => [
                        'workspace' => $workspace->getId(),
                        'uid' => ''
                    ]
                ]);


                logActivity('[BizyCloud]: #2 (service id: '.$serviceId.') project created: (TEMPLATE) for the '.$workspaceName . ' (ID: '. (string) $workspace->getId().') TEMPLATE ID:'.$templateProjectId.'', 0);
            } catch (\Throwable $e) {

                logActivity('![BizyCloud]: #2 (service id: '.$serviceId.') there was a problem when creating the project (TEMPLATE) for the '.$workspaceName . '(ID: '. (string) $workspace->getId().') TEMPLATE ID:'.$templateProjectId.' :'.$e->getMessage() , 0);
            }

        } else {
            try {
                $project = $client->projects->create(new CreateProjectRequest([
                    'name' => $projectName,
                    'workspace' => (string) $workspace->getId()
                ]));

                logActivity('[BizyCloud]: #2 (service id: '.$serviceId.') project created: '.$projectName . ' for the '.$workspaceName . ' (ID: '. (string) $workspace->getId().')', 0);
            } catch (\Throwable $e) {
                logActivity('![BizyCloud]: #2 (service id: '.$serviceId.') there was a problem when creating the project '.$projectName .' for the '.$workspaceName . '(ID: '. (string) $workspace->getId().'): '.$e->getMessage() , 0);
            }
        }


        $userId = null;

        // Step 3. Create a User
        try {
            $user = $client->users->create(new CreateUserRequest([
                'email' => $userEmail,
                'status' => 'verified'
            ]));
            $userId = $user->getId();
            logActivity('[BizyCloud]: #3 (service id: '.$serviceId.') user created: '.$userEmail, 0);
        } catch (\Throwable $e) {
            logActivity('![BizyCloud]: #3 (service id: '.$serviceId.') there was a problem creating a user '.$userEmail.': '.$e->getMessage(), 0);

            $apiResponseUsers = $client->users->list(new ListUserRequest(1, 200, $userEmail));
            $userCheck = $apiResponseUsers->toArray();
            if (isset($userCheck[0]) && $userCheck[0]['email'] === $userEmail){
                $userId = $userCheck[0]['id'];
            }

        }

        // Step 4.1. Invite a User to the Workspace as a team member and assign them the Manager role
        try {
            $teamMember = $client->teamMembers->create(new CreateTeamMemberRequest([
                'user' => (string) $userId,
                'workspace' => (string) $workspace->getId(),
                'role' => 'manager'
            ]));
            logActivity('[BizyCloud]: #4 (service id: '.$serviceId.') User assigned to workspace  '.$workspaceName . ' (ID: '. (string) $workspace->getId().') with "manager" role : '.$userEmail, 0);
        } catch (\Throwable $e) {
            logActivity('![BizyCloud]: #4 (service id: '.$serviceId.')There was a problem assigning a user to workspace '.$workspaceName . ' (ID: '. (string) $workspace->getId().') with "manager" role  '.$userEmail . ': '.$e->getMessage(), 0);
        }

        // Step 4.2. Change User status to approved
        try {
            $teamMember = $client->teamMembers->update(new UpdateTeamMemberRequest($teamMember->getId(), [
                'status' => 'approved'
            ]));
            logActivity('[BizyCloud]: #5 (service id: '.$serviceId.') User approved: '.$userEmail, 0);
        } catch (\Throwable $e) {
            logActivity('![BizyCloud]: #5 (service id: '.$serviceId.') There was a problem with user approval: '.$userEmail. ': '.$e->getMessage(), 0);

        }


        // // Step 5. Authorize the Project.uid via SSO
        // try {
        //     $redirectUrl = $client->sso->getRedirectUrl($project->getId(), ['email' => $userEmail]);

        //     header('Location: ' . $redirectUrl);
        //     exit;
        // } catch (\Throwable $e) {
        //     echo '<pre>';
        //     print_r($e->getMessage());
        //     echo '</pre>';
        // }
    }




}
