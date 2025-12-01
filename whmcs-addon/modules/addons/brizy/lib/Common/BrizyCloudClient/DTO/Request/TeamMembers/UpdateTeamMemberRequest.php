<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers;

class UpdateTeamMemberRequest
{
    private array $data;
    private int $id;

    public function __construct(int $id, array $data = [])
    {
        $this->data = $data;
        $this->id = $id;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function getTeamMemberId(): int
    {
        return $this->id;
    }
}
