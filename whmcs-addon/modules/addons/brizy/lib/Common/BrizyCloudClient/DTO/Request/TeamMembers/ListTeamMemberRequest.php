<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\TeamMembers;

class ListTeamMemberRequest
{
    private int $workspaceId;
    private ?int $count;
    private ?int $page;

    public function __construct($workspaceId, ?int $page = null, ?int $count = null)
    {
        if ($page !== null && $page < 0) {
            throw new \InvalidArgumentException('Page must be >= 0');
        }

        if ($count !== null && ($count < 1 || $count > 200)) {
            throw new \InvalidArgumentException('Count must be between 1 and 200');
        }

        $this->workspaceId = $workspaceId;
        $this->count = $count;
        $this->page = $page;
    }

    public function toArray(): array
    {
        return array_filter([
            'workspace' => $this->workspaceId,
            'page'  => $this->page,
            'count' => $this->count
        ], fn($v) => $v !== null);
    }
}
