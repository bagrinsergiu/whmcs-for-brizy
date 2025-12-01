<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Service;

use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Http\HttpClient;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users\ListUserRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users\CreateUserRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users\GetUserRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users\DeleteUserRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users\UpdateUserRequest;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\Users\UserResponse;
use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\Users\UserResponseCollection;

class UserService
{
    private $http;
    public function __construct(HttpClient $http) {
        $this->http = $http;
    }


    public function list(ListUserRequest $r): UserResponseCollection
    {
        $raw = $this->http->request('GET', 'users', [
            'query' => $r->toArray()
        ]);

        return UserResponseCollection::fromArray($raw);
    }

    public function get(GetUserRequest $r): UserResponse
    {
        $raw = $this->http->request('GET', 'users/' . $r->getUserId());

        return UserResponse::fromArray($raw);
    }

    public function create(CreateUserRequest $r): UserResponse
    {
        $raw = $this->http->request('POST', 'users', [
            'json' => $r->toArray()
        ]);

        return UserResponse::fromArray($raw);
    }

    public function delete(DeleteUserRequest $r): void
    {
        $this->http->request('DELETE', 'users/' . $r->getUserId());
    }

    public function update(UpdateUserRequest $r): UserResponse
    {
        $raw = $this->http->request('PATCH', 'users/' . $r->getUserId(), [
            'json' => $r->toArray()
        ]);

        return UserResponse::fromArray($raw);
    }
}