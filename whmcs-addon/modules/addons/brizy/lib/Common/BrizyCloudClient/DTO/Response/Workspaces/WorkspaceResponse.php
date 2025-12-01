<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\Workspaces;

use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\Workspaces\RoleResponse;

class WorkspaceResponse
{
    private int $id;
    private string $name;
    private int $projectCount;
    private RoleResponse $role;

    public function __construct(int $id, string $name, int $projectCount, RoleResponse $role)
    {
        $this->id = $id;
        $this->name = $name;
        $this->projectCount = $projectCount;
        $this->role = $role;
    }

    public static function fromArray(array $data): self
    {
        $item = $data['data'] ?? $data;

        return new self(
            (int) $item['id'],
            $item['name'],
            (int) $item['project_count'],
            RoleResponse::fromArray($item['role'])
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProjectCount(): int
    {
        return $this->projectCount;
    }

    public function getRole(): RoleResponse
    {
        return $this->role;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'project_count' => $this->projectCount,
            'role' => $this->role->toArray()
        ];
    }
}
