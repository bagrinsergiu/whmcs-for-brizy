<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Service;

use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Http\HttpClient;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces\ListWorkspaceRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces\CreateWorkspaceRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces\CountWorkspaceRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces\DeleteWorkspaceRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces\UpdateWorkspaceRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\Workspaces\WorkspaceResponse;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\Workspaces\WorkspaceResponseCollection;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\Workspaces\CountResponse;

class WorkspaceService
{
    private $http;
    public function __construct(HttpClient $http) {
        $this->http = $http;
    }


    public function list(ListWorkspaceRequest $r): WorkspaceResponseCollection
    {
        $raw = $this->http->request('GET', 'workspaces', [
            'query' => $r->toArray()
        ]);

        return WorkspaceResponseCollection::fromArray($raw);
    }

    public function create(CreateWorkspaceRequest $r): WorkspaceResponse
    {
        $raw = $this->http->request('POST', 'workspaces', [
            'json' => $r->toArray()
        ]);

        return WorkspaceResponse::fromArray($raw);
    }

    public function count(CountWorkspaceRequest $r): CountResponse
    {
        $raw = $this->http->request('GET', 'workspaces/count', [
            'query' => $r->getWorkspaceRole()
        ]);

        return CountResponse::fromArray($raw);
    }

    public function delete(DeleteWorkspaceRequest $r): void
    {
        $this->http->request('DELETE', 'workspaces/' . $r->getWorkspaceId());
    }

    public function update(UpdateWorkspaceRequest $r): WorkspaceResponse
    {
        $raw = $this->http->request('PUT', 'workspaces/' . $r->getWorkspaceId(), [
            'json' => $r->toArray()
        ]);

        return WorkspaceResponse::fromArray($raw);
    }
}
