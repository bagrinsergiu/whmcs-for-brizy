<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces;

class UpdateWorkspaceRequest
{
    public function __construct(int $id, private array $data = []) {}

    public function toArray(): array
    {
        return $this->data;
    }

    public function getWorkspaceId(): int
    {
        return $this->id;
    }
}
