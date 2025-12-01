<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Service;

use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Http\HttpClient;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\ListTeamMemberRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\CreateTeamMemberRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\DeleteTeamMemberRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers\UpdateTeamMemberRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\TeamMembers\TeamMemberResponse;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\TeamMembers\TeamMemberResponseCollection;

class TeamMemberService
{
    private $http;
    public function __construct(HttpClient $http) {
        $this->http = $http;
    }


    public function list(ListTeamMemberRequest $r): TeamMemberResponseCollection
    {
        $raw = $this->http->request('GET', 'team_members', [
            'query' => $r->toArray()
        ]);

        return TeamMemberResponseCollection::fromArray($raw);
    }

    public function create(CreateTeamMemberRequest $r): TeamMemberResponse
    {
        $raw = $this->http->request('POST', 'team_members', [
            'json' => $r->toArray()
        ]);

        return TeamMemberResponse::fromArray($raw);
    }

    public function delete(DeleteTeamMemberRequest $r): void
    {
        $this->http->request('DELETE', 'team_members/' . $r->getTeamMemberId());
    }

    public function update(UpdateTeamMemberRequest $r): TeamMemberResponse
    {
        $raw = $this->http->request('PATCH', 'team_members/' . $r->getTeamMemberId(), [
            'json' => $r->toArray()
        ]);

        return TeamMemberResponse::fromArray($raw);
    }
}