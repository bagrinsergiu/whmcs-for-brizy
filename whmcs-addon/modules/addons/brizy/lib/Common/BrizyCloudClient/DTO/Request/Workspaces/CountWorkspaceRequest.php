<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces;

class CountWorkspaceRequest
{
    public function __construct(string $role) {}

    public function getWorkspaceRole(): string
    {
        return $this->role;
    }
}
