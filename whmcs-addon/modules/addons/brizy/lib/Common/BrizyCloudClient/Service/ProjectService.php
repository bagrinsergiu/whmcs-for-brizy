<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Service;

use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Http\HttpClient;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\ListProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\CreateProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\GetProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\DeleteProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\UpdateProjectRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\ListProjectLeadsRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects\ExportProjectLeadsRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\Projects\ProjectResponse;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\Projects\ProjectResponseCollection;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\Projects\LeadResponseCollection;

class ProjectService
{
    private $http;
    public function __construct(HttpClient $http) {
        $this->http = $http;
    }


    public function list(ListProjectRequest $r): ProjectResponseCollection
    {
        $raw = $this->http->request('GET', 'projects', [
            'query' => $r->toArray()
        ]);

        return ProjectResponseCollection::fromArray($raw);
    }

    public function get(GetProjectRequest $r): ProjectResponse
    {
        $raw = $this->http->request('GET', 'projects/' . $r->getProjectId());

        return ProjectResponse::fromArray($raw);
    }

    public function create(CreateProjectRequest $r): ProjectResponse
    {
        $raw = $this->http->request('POST', 'projects', [
            'json' => $r->toArray()
        ]);

        return ProjectResponse::fromArray($raw);
    }

    public function delete(DeleteProjectRequest $r): void
    {
        $this->http->request('DELETE', 'projects/' . $r->getProjectId());
    }

    public function update(UpdateProjectRequest $r): ProjectResponse
    {
        $raw = $this->http->request('PATCH', 'projects/' . $r->getProjectId(), [
            'json' => $r->toArray()
        ]);

        return ProjectResponse::fromArray($raw);
    }

    public function listLeads(ListProjectLeadsRequest $r): LeadResponseCollection
    {
        $raw = $this->http->request('GET', 'projects/' . $r->getProjectId() . '/leads', [
            'query' => $r->toArray()
        ]);

        return LeadResponseCollection::fromArray($raw);
    }

    public function exportLeads(ExportProjectLeadsRequest $r): string
    {
        return $this->http->requestRaw('GET', 'projects/' . $r->getProjectId() . '/leads/export', [
            'query' => $r->toArray()
        ]);
    }

    public function rawPost($endpoint, $data) {
        return $this->http->requestRaw('GET', 'projects/'. '/leads/export', [
            'query' => $data
        ]);
    }

    public function rawGet($endpoint, $data) {
        return $this->http->requestRaw('GET', 'projects/'. '/leads/export', [
            'query' => $data
        ]);
    }
}