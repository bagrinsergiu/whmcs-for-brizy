<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users;

class UpdateUserRequest
{
    public function __construct(int $id, private array $data = []) {}

    public function toArray(): array
    {
        return $this->data;
    }

    public function getUserId(): int
    {
        return $this->id;
    }
}
