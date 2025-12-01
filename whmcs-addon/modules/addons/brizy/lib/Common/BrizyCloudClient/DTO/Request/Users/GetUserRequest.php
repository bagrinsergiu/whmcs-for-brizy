<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users;

class GetUserRequest
{
    public function __construct(int $id) {}

    public function getUserId(): int
    {
        return $this->id;
    }
}
