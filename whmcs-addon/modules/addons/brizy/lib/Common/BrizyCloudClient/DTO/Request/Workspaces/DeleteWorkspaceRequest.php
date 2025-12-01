<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces;

class DeleteWorkspaceRequest
{
    public function __construct(int $id) {}

    public function getWorkspaceId(): int
    {
        return $this->id;
    }
}
