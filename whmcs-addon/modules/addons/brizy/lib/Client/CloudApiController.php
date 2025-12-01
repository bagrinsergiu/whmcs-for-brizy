<?php

namespace WHMCS\Module\Addon\Brizy\Client;

use WHMCS\Module\Addon\Brizy\Api\DefaultApiController;
use WHMCS\Module\Addon\Brizy\Common\Settings;
use WHMCS\Module\Addon\Brizy\Common\Translations;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\BrizyClient;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\ListProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\CreateProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\DeleteProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\GetProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\UpdateProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\ListTeamMemberRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\CreateTeamMemberRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\UpdateTeamMemberRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\DeleteTeamMemberRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users\ListUserRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users\CreateUserRequest;
use WHMCS\Module\Addon\Brizy\Common\CpanelDefault;

/**
 * Client area API controller
 */
class CloudApiController extends DefaultApiController
{

    /**
     * Service ID
     *
     * @var integer
     */
    private $serviceId;
    private $service;
    private $cloudApiClient;
    private $cloudAiApiClient;
    private $workspaceId;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->serviceId = (int)$this->inputGet('serviceId');

        $cloudApiKey = Settings::get('cloud_access_key');
        $publicPartnerId = Settings::get('cloud_public_partner_id');
        $wlDashboardPath = rtrim(Settings::get('cloud_wl_dashboard_domain'), '/') . '/';
        $baseUri = $wlDashboardPath . 'api/2.0/';
        $aiDashboardUri =  rtrim(Settings::get('cloud_wl_dashboard_ai'), '/') . '/';

        $this->cloudAiApiClient = new BrizyClient(
            $cloudApiKey,
            $publicPartnerId,
            $aiDashboardUri,
            $aiDashboardUri.'api/'
        );

        $this->cloudApiClient = new BrizyClient(
            $cloudApiKey,
            $publicPartnerId,
            $wlDashboardPath,
            $baseUri
        );

        $currentUser =  new \WHMCS\Authentication\CurrentUser;

        if ($this->serviceId) {

            $currentUserId = $currentUser->client()->id;

            $this->service = \WHMCS\Service\Service::where('id', $this->serviceId)
                ->where('userId',  $currentUserId)
                ->first();

            if (!$this->service) {
                $this->respondWithError(Translations::$_['client']['api']['repsonse']['accessRestricted'] . ' (#1)');
            }

            if (!$currentUser->user()) {
                $this->respondWithError(Translations::$_['client']['api']['repsonse']['accessRestricted']  . ' (#2)');
            }

        } else {
            $this->respondWithError(Translations::$_['client']['api']['repsonse']['accessRestricted']  . ' (#4)');
        }

        $this->workspaceId = $this->service->serviceProperties->get(Settings::$servicePropertiesWorkspace);

    }

    public function deleteMember() {

        $teamMemberId = $this->inputGet('teamMemberId');

        try {
            $apiResponseMembers = $this->cloudApiClient->teamMembers->list(new ListTeamMemberRequest($this->workspaceId, 1, 200));
        } catch (\Throwable $e) {

            $this->respondInternalError($e->getMessage());
        }


        if(count($apiResponseMembers->toArray()) <= 1) {
            $this->respondWithError("You can't remove this team member");
        }


        $managers = array_filter($apiResponseMembers->toArray(), function($user) {
            return $user['role'] == 'manager';
        });


        $workspaceOwnerEmail = $this->service->serviceProperties->get(Settings::$servicePropertiesWorkspaceCoreTeamMember);

        $apiResponseUsers = $this->cloudApiClient->users->list(new ListUserRequest(1, 200, $workspaceOwnerEmail));
        $ownerResponse = $apiResponseUsers->toArray();


        // workspace owner check
        $owner = null;
        if (isset($ownerResponse[0])){
            $ownerUser = $ownerResponse[0];
            $owner = reset(array_filter($apiResponseMembers->toArray(),  function ($user) use ($ownerUser) {
                    return $user['user'] == $ownerUser['id'];
            }));
        }

        if ($owner && $teamMemberId == $owner['id']) {
            $this->respondWithError("You can't remove this team member");
        }

        try {
            $apiResponseMembers = $this->cloudApiClient->teamMembers->delete(new DeleteTeamMemberRequest($teamMemberId));
        } catch (\Throwable $e) {

            $this->respondWithError($e->getMessage());
        }

        $this->respond();
    }


    public function getProjects() {

        if (!$this->workspaceId) {
            $this->respondWithError('Error: no workspace');
        }

        try {
            $apiResponse = $this->cloudApiClient->projects->list(new ListProjectRequest($this->workspaceId));
        } catch (\Throwable $e) {
            $this->respondInternalError();
        }

        $projects = $this->filterArraysByFields(
            ['status', 'id', 'name', 'site_title', 'deployment_status'],
            $apiResponse->toArray()
        );

        $wlProjectPreviewUrl = rtrim(Settings::get('cloud_wl_previews_domain'), '/');

        $projects = array_map(function($project) use ($wlProjectPreviewUrl) {
            $project['domains'] = [];
            $project['preview_domain'] = rtrim(str_replace(
                ['https://', 'http://'],
                '',
                $wlProjectPreviewUrl
            ), '/');

            try {
                $domainsResponse = $this->cloudApiClient->http->request('GET','projects/'.$project['id'].'/domains');

                if ($domainsResponse) {
                    $project['domains'] = array_map(function($domain)  use ($wlProjectPreviewUrl) {
                        $domain['full_name'] = $domain['name'];

                        if ($domain['type'] === 'subdomain') {
                            $domain['full_name'] = str_replace(
                                ['https://', 'http://'],
                                ['https://'. $domain['name'].'.', 'http://' . $domain['name'] . '.'],
                                $wlProjectPreviewUrl
                            );
                        }

                        if ($domain['type'] === 'third-party') {
                            $domain['full_name'] = 'http://'. $domain['name'];
                        }

                        return $domain;

                    }, $domainsResponse);
                } else {

                }

            } catch (\Throwable $e) {

            }

            return $project;

        }, $projects);

        $this->respond($projects);
    }

    public function getProjectLink() {

        $projectId = (int)$this->inputGet('projectId');

        if (!$this->checkIfUserHaveAccessToProject($projectId)) {
            $this->respondWithError('Project does not exist');
        }

        try {
            $projectResponse = $this->cloudApiClient->projects->get(new GetProjectRequest($projectId));
        } catch (\Throwable $e) {
            $this->respondWithError('Project does not exist');
        }

        $project = $projectResponse->toArray();
        $userEmail = $this->service->serviceProperties->get(Settings::$servicePropertiesWorkspaceCoreTeamMember);

        if (!$userEmail) {
            $userEmail = $this->service->client->email;
        }

        try {
            $redirectUrl = $this->cloudApiClient->sso->getRedirectUrl($project['uid'], ['email' => $userEmail]);
            $this->respond(['url' => $redirectUrl]);
        } catch (\Throwable $e) {
            $this->respondInternalError();
        }
    }

    public function renameProject() {
        $projectName = $this->input['name'];

        if (!$this->workspaceId) {
            $this->respondWithError('Error: no workspace');
        }

        $projectId = (int)$this->input['id'];

        if (!$this->checkIfUserHaveAccessToProject($projectId)) {
            $this->respondWithError('Project does not exist');
        }

        if (!$projectName || strlen($projectName) < 3) {
            $this->respondWithError('Error: name too short');
        }

        try {
            $project = $this->cloudApiClient->projects->update(new UpdateProjectRequest($projectId, [
                'name' => $projectName,
            ]));

        } catch (\Throwable $e) {
            $this->respondInternalError();
        }

        $this->respond();
    }

    public function addNewProject() {
        $projectName = $this->input['projectName'];
        $projectId = $this->input['projectId'];

        if (!$this->workspaceId) {
            $this->respondWithError('Error: no workspace');
        }

        if (!$projectName || strlen($projectName) < 3 ) {
            $this->respondWithError('Error: name too short');
        }


        if ($projectId) {
            try {
                $duplicateProjectResponse = $this->cloudApiClient->http->request('POST','projects/'.$projectId.'/duplicates', [
                    'json' => [
                        'workspace' => $this->workspaceId,
                        'uid' => ''
                    ]
                ]);
            } catch (\Throwable $e) {
                $this->respondWithError($e->getMessage());
            }


            try {
                $projectRenameResponse = $this->cloudApiClient->projects->update(new UpdateProjectRequest($duplicateProjectResponse['id'], [
                    'name' => $projectName,
                ]));
            } catch (\Throwable $e) {

            }
        } else {
            try {
                $payload = [
                    'name' => $projectName,
                    'workspace' => $this->workspaceId,

                ];

                $project = $this->cloudApiClient->projects->create(new CreateProjectRequest($payload));

            } catch (\Throwable $e) {
                $this->respondWithError($e->getMessage());
            }


        }

        $this->respond();
    }


    public function deleteProject() {
        $projectId = (int)$this->inputGet('projectId');

        if (!$this->workspaceId) {
            $this->respondWithError('Error: no workspace');
        }

        if (!$this->checkIfUserHaveAccessToProject($projectId)) {
            $this->respondWithError('Project does not exist');
        }

        try {
            $response = $this->cloudApiClient->projects->delete(new DeleteProjectRequest($projectId));
        } catch (\Throwable $e) {
            $this->respondInternalError();
        }

        $this->respond();
    }

    public function getDownloadLink() {

        $projectId = $this->inputGet('projectId');


        if (!$this->workspaceId) {
            $this->respondWithError('Error: no workspace');
        }

        if (!$this->checkIfUserHaveAccessToProject($projectId)) {
            $this->respondWithError('Project does not exist');
        }

        try {
            $syncDownloadResponse = $this->cloudApiClient->http->request('GET','projects/'.$projectId.'/sync/download');
        } catch (\Throwable $e) {

            $this->respondInternalError($e->getMessage());
        }

        $this->respond($syncDownloadResponse);
    }

    public function getMembers() {

        if (!$this->workspaceId) {
            $this->respondWithError('Error: no workspace');
        }

        try {
            $apiResponseUsers = $this->cloudApiClient->users->list(new ListUserRequest(1, 200));
        } catch (\Throwable $e) {

            $this->respondInternalError($e->getMessage());
        }

        try {
            $apiResponseMembers = $this->cloudApiClient->teamMembers->list(new ListTeamMemberRequest($this->workspaceId, 1, 200));
        } catch (\Throwable $e) {

            $this->respondInternalError($e->getMessage());
        }

        $membersAsUserId = array_column($apiResponseMembers->toArray(), null, 'user');
        $workspaceOwnerEmail = trim($this->service->serviceProperties->get(Settings::$servicePropertiesWorkspaceCoreTeamMember));

        $teamMembers = [];
        foreach($apiResponseUsers->toArray() as $user) {
            if (isset($membersAsUserId[$user['id']])) {
                $teamMember = $membersAsUserId[$user['id']];
                $teamMember['team_member_id'] = $teamMember['id'];
                $teamMember['owner'] = $workspaceOwnerEmail == $user['email'];
                $teamMembers[]  = array_merge($teamMember, $user);
            }
        }

        $this->respond($this->filterArraysByFields(
            ['email', 'owner', 'role', 'status', 'team_member_id', 'name_l', 'name_f'],
            $teamMembers
        ));
    }


    public function addNewMember() {
        $email = $this->input['email'];
        $role = $this->input['role'];

        if (!$this->workspaceId) {
            $this->respondWithError('Error: no workspace');
        }

        try {
            $apiResponseUsers = $this->cloudApiClient->users->list(new ListUserRequest(1, 200, $email));
        } catch (\Throwable $e) {
            $this->respondWithError($e->getMessage());
        }

        if (count($apiResponseUsers->toArray()) > 0) {
            $userId = $apiResponseUsers->toArray()[0]['id'];
        } else {
            try {
                $user = $this->cloudApiClient->users->create(new CreateUserRequest([
                    'email' => $email,
                    'status' => 'verified'
                ]));

            } catch (\Throwable $e) {
                $this->respondWithError($e->getMessage());
            }

            $userId = $user->getId();
        }

        try {
            $teamMember = $this->cloudApiClient->teamMembers->create(new CreateTeamMemberRequest([
                'user' => (string) $userId,
                'workspace' => (string) $this->workspaceId,
                'role' => $role
            ]));

        } catch (\Throwable $e) {
            $this->respondWithError($e->getMessage());
        }

        try {
            $teamMember = $this->cloudApiClient->teamMembers->update(new UpdateTeamMemberRequest($teamMember->getId(), [
                'status' => 'approved'
            ]));

        } catch (\Throwable $e) {
            $this->respondWithError($e->getMessage());
        }

        $this->respond();
    }

    public function changeMemberRole() {
        $teamMemberId = (int)$this->input['teamMemberId'];
        $role = $this->input['role'];

        try {
            $teamMemberUpdateResponse = $this->cloudApiClient->teamMembers->update(new UpdateTeamMemberRequest($teamMemberId, [
                'role' => $role
            ]));

        } catch (\Throwable $e) {
            $this->respondWithError($e->getMessage());
        }

        $this->respond();
    }

    public  function changeProjectDomain() {
        $projectId = (int)$this->input['projectId'];
        $subdomain = (bool)$this->input['subdomain'];
        $value = $this->input['value'];

        if (!$this->workspaceId) {
            $this->respondWithError('Error: no workspace');
        }

        if (!$this->checkIfUserHaveAccessToProject($projectId)) {
            $this->respondWithError('Project does not exist');
        }

        try {
            $currentDomains = $this->cloudApiClient->http->request('GET','projects/'.$projectId.'/domains');
        } catch (\Throwable $e) {
            $this->respondWithError($e->getMessage());
        }

        foreach($currentDomains as $projectDomain) {

            if ($subdomain && $projectDomain['type'] === 'subdomain') {
                try {
                    $domainsResponse = $this->cloudApiClient->http->request('PUT','projects/'.$projectDomain['project'].'/domains/'.$projectDomain['id'], [
                        'json' => ['name' => $value]
                    ]);
                } catch (\Throwable $e) {
                    $this->respondWithError($e->getMessage());
                }

                $this->respond();
            }


            if (!$subdomain && $projectDomain['type'] === 'third-party') {

                try {
                    $domainsResponse = $this->cloudApiClient->http->request('DELETE','projects/'.$projectDomain['project'].'/domains/'.$projectDomain['id']);
                } catch (\Throwable $e) {
                    $this->respondWithError($e->getMessage());
                }
            }
        }

        try {
            $domainsResponse = $this->cloudApiClient->http->request('POST','projects/'.$projectId.'/domains', [
                'json' => ['name' => $value]
            ]);
        } catch (\Throwable $e) {
            $this->respondWithError($e->getMessage());
        }

        $this->respond();
    }

    public function deleteProjectDomain() {
        $projectId = (int)$this->input['projectId'];
        $domainId = (int)$this->input['domainId'];

        if (!$this->workspaceId) {
            $this->respondWithError('Error: no workspace');
        }

        if (!$this->checkIfUserHaveAccessToProject($projectId)) {
            $this->respondWithError('Project does not exist');
        }

        try {
            $domainsResponse = $this->cloudApiClient->http->request('DELETE','projects/'.$projectId.'/domains/'.$domainId);
        } catch (\Throwable $e) {
            $this->respondWithError($e->getMessage());
        }

        $this->respond();
    }

    public function getBusiness() {

        $lang = $this->input['lang'];
        $input = $this->input['phrase'];

        if (!$this->workspaceId) {
            $this->respondWithError('Error: no workspace');
        }

        $country = '';
        $googleMapsApiKey = Settings::get('cloud_wl_google_maps_api_key');

        if (!$googleMapsApiKey) {
            $this->respond([]);
        }

        $data = [
            'json' => [
                'businessType' => "google-business",
                'query' => $input,
                'country' => $country
            ],
            'headers' => [
                'x-auth-api-key' => $googleMapsApiKey,
            ],
        ];

        try {
            $aiBusinessResponse = $this->cloudAiApiClient->http->request('POST','get-business', $data);
        } catch (\Throwable $e) {
            $this->respondWithError($e->getMessage());
        }

        $response =[];

        if (isset($aiBusinessResponse['suggestions'])) {
            foreach($aiBusinessResponse['suggestions'] as $aiBusiness) {
                $place = $aiBusiness['placePrediction'];

                $response[] = [
                    'placeId' => $place['placeId'],
                    'text' => $place['structuredFormat']['mainText']['text'],
                    'secondaryText' => $place['structuredFormat']['secondaryText']['text'],
                ];
            }
        }

        $this->respond($response);
    }

    public function getIdeas() {

        $descriptionString = preg_replace('/[^A-Za-z0-9 !@#$%^&*().]/u','', strip_tags($this->input['description']));
        try {
            $aiResponse = $this->cloudAiApiClient->http->request('POST','companies', [
                'json' => [
                    'prompt' => $descriptionString
                    ]
            ]);
        } catch (\Throwable $e) {
            $this->respondWithError($e->getMessage());
        }

       $this->respond($aiResponse);
    }

    public function buildWebsite() {

        $company = $this->input['company'];
        $lang = $this->input['lang'];
        $gId = $this->input['gId'];
        $industry = $this->input['industry'];
        $phone = $this->input['phone'];
        $email = $this->input['email'];

        $descriptionString = preg_replace('/[^A-Za-z0-9 !@#$%^&*().]/u','', strip_tags($this->input['description']));
        $payload =  [
            'company' => $company,
            'businessId' => $gId,
            'businessType' => "google-business",
            'lang' => $lang,
            'prompt' => $industry,
            'description' => $descriptionString,
            'phone' => $phone,
            'email' => $email,
        ];

        $headers = [];

        if($gId) {

            $googleMapsApiKey = Settings::get('cloud_wl_google_maps_api_key');
            $headers = ['x-auth-api-key' => $googleMapsApiKey];

        }
        try {
            $aiBuildWebsiteResponse = $this->cloudAiApiClient->http->request('POST','projects', [
                'json' => $payload,
                'headers' => $headers

            ]);
        } catch (\Throwable $e) {
            $this->respondWithError($e->getMessage());
        }


        $url = $this->cloudAiApiClient->sso->getAiProjectRedirectUrl($aiBuildWebsiteResponse['id'], $lang);

        $aiBuildWebsiteResponse['url'] = $url;
        $aiBuildWebsiteResponse['lang'] = $lang;

        $this->respond($aiBuildWebsiteResponse);
    }

    public function buildWebsitePages () {

        session_write_close();

        if (!$this->workspaceId) {
            $this->respondWithError('Error: no workspace');
        }

        $id = $this->input['id'];
        $pages = $this->input['pages'];

        try {
            $aiPagesResponse = $this->cloudAiApiClient->http->request('POST','projects/'.$id.'/pages', [
                'json' => [
                    'pages' => $pages
                ]
            ]);
        } catch (\Throwable $e) {
            sleep(30);
            $this->respond();
        }

        $this->respond();
    }

    public function finishWebsite () {



        if (!$this->workspaceId) {
            $this->respondWithError('Error: no workspace');
        }

        $id = $this->input['id'];

        try {
            $aiCloudResponse = $this->cloudAiApiClient->http->requestRaw('POST','projects/'.$id.'/cloud', [
                'json' => [],
                'headers' => [
                    'workspace-id' => $this->workspaceId,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->respondWithError($e->getMessage());
        }

        $this->respond($aiCloudResponse);
    }


    public function authorizeWebsite() {

        if (!$this->workspaceId) {
            $this->respondWithError('Error: no workspace');
        }

        $id = $this->input['id'];

        try {
            $projectResponse = $this->cloudApiClient->projects->get(new GetProjectRequest($id));
        } catch (\Throwable $e) {
            $this->respondWithError($e->getMessage());
        }

        $userEmail = $this->service->serviceProperties->get(Settings::$servicePropertiesWorkspaceCoreTeamMember);

        if (!$userEmail) {
            $userEmail = $this->service->client->email;
        }

        $redirectUrl = $this->cloudApiClient->sso->getRedirectUrl($id, ['email' => $userEmail]);

        $this->respond(['url' => $redirectUrl]);
    }


    public function getAiWebsiteData() {

        $id = $this->input['id'];

        try {
            $aiWebsiteResponse = $this->cloudAiApiClient->http->request('GET','projects/'.$id);
        } catch (\Throwable $e) {
            $this->respondWithError($e->getMessage());
        }


        $this->respond($aiWebsiteResponse);
    }

    public function getTemplatesCategories() {

        try {
            $templateCategoriesResponse = $this->cloudApiClient->http->request('GET','template_categories');
        } catch (\Throwable $e) {
            $this->respondWithError($e->getMessage());
        }


        $this->respond($templateCategoriesResponse);
    }

    public function getTemplates() {

        $pageItems = 100;
        $templates = [];
        $page = 0;

        do {
            if (++$page >= 5) {
                $this->respondWithError('Max limit - 5');
            };

            try {
                $templateCategoriesResponse = $this->cloudApiClient->http->request('GET','templates', [
                    'query' => [
                        'page' => $page,
                        'limit' => $pageItems,
                    ]
                ]);

            } catch (\Throwable $e) {
                $this->respondWithError($e->getMessage());
            }

            $templates = array_merge($templates, $templateCategoriesResponse);

        } while (count($templateCategoriesResponse) >= $pageItems);


        $this->respond($templates);
    }

    public function deployProject() {
        $projectId = $this->inputGet('projectId');

        if (!$this->workspaceId) {
            $this->respondWithError('Error: no workspace');
        }

        if (!$this->checkIfUserHaveAccessToProject($projectId)) {
            $this->respondWithError('Project does not exist');
        }

        try {
            $syncDownloadResponse = $this->cloudApiClient->http->request('GET','projects/'.$projectId.'/sync/download');
        } catch (\Throwable $e) {

            $this->respondInternalError($e->getMessage());
        }

        if (isset($syncDownloadResponse['download_url'])) {


            $cp = new CpanelDefault($this->service);
            $cp->setInstallerTemplateFile('bci.php');
            $cp->setPlaceholderToReplace([
                '{bc_project_download_url}' => $syncDownloadResponse['download_url']
            ]);

            $cp->putInstallationScriptOnServer();
            $cp->runInstallationFile();
        }

        $this->respond([]);
    }

    private function filterArraysByFields(array $fields, array $data): array {
        $result = [];

        foreach ($data as $row) {
            $filteredRow = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $row)) {
                    $filteredRow[$field] = $row[$field];
                }
            }
            $result[] = $filteredRow;
        }

        return $result;
    }


    private function checkIfUserHaveAccessToProject($projectId) {



        if (!$this->workspaceId) {
            return false;
        }

        try {
            $projectResponse = $this->cloudApiClient->projects->get(new GetProjectRequest($projectId));
        } catch (\Throwable $e) {
            return false;
        }

        if ($projectResponse && $projectResponse->getWorkspace() === (int)$this->workspaceId) {
            return true;
        }

        return false;
    }

}
