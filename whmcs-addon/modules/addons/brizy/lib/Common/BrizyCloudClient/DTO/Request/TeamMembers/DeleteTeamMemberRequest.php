<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers;

class DeleteTeamMemberRequest
{
    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getTeamMemberId(): int
    {
        return $this->id;
    }
}
