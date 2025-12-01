<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\BrizyClient;

use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Http\HttpClient;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Sso\SsoManager;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Service\WorkspaceService;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Service\ProjectService;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Service\SimpleService;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Service\UserService;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Service\TeamMemberService;

class BrizyClient
{
    public WorkspaceService $workspaces;
    public ProjectService $projects;
    public UserService $users;
    public TeamMemberService $teamMembers;
    public SsoManager $sso;
    public SimpleService $request;

    public function __construct(string $secretKey, string $partnerId, string $whiteLabelDomain, string $baseUri)
    {
        $http = new HttpClient($secretKey, $baseUri);

        $this->workspaces = new WorkspaceService($http);
        $this->projects = new ProjectService($http);
        $this->users = new UserService($http);
        $this->teamMembers = new TeamMemberService($http);
        $this->sso = new SsoManager($partnerId, $secretKey, $whiteLabelDomain);
        $this->request = new SimpleService($http);
    }
}
