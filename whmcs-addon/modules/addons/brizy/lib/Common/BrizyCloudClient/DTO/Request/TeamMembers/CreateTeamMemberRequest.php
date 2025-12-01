<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers;

class CreateTeamMemberRequest
{
    public $data;

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
