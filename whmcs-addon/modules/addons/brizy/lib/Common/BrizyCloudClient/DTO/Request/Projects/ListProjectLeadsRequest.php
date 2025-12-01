<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects;

class ListProjectLeadsRequest
{
    public function __construct(int $projectId, private ?int $page = null, private ?int $count = null)
    {
        if ($page !== null && $page < 0) {
            throw new \InvalidArgumentException('Page must be >= 0');
        }

        if ($count !== null && ($count < 1 || $count > 200)) {
            throw new \InvalidArgumentException('Count must be between 1 and 200');
        }
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function toArray(): array
    {
        return array_filter([
            'page'  => $this->page,
            'count' => $this->count
        ], fn($v) => $v !== null);
    }
}
