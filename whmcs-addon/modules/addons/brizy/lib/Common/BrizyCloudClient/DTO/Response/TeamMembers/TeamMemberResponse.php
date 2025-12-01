<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\TeamMembers;

class TeamMemberResponse
{
    private $id;
    private $workspace;
    private $user;
    private $role;
    private $status;

    public function __construct(
        $id,
        $workspace,
        $user,
        $role,
        $status
    ) {
        $this->id = $id;
        $this->workspace = $workspace;
        $this->user = $user;
        $this->role = $role;
        $this->status = $status;
    }

    public static function fromArray(array $data): self
    {
        $item = $data['data'] ?? $data;

        return new self(
            (int) $item['id'],
            (int) $item['workspace'],
            (int) $item['user'],
            $item['role'],
            $item['status']
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getWorkspace(): int
    {
        return $this->workspace;
    }

    public function getUser(): int
    {
        return $this->user;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'workspace' => $this->workspace,
            'user' => $this->user,
            'role' => $this->role,
            'status' => $this->status
        ];
    }
}
