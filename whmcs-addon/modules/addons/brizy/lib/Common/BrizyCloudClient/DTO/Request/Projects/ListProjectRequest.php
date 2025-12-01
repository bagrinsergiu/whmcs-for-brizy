<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects;

class ListProjectRequest
{
    private ?int $workspace;
    private ?int $page;
    private ?int $count;

    public function __construct(?int $workspace = null, ?int $page = null, ?int $count = null)
    {
        if ($page !== null && $page < 0) {
            throw new \InvalidArgumentException('Page must be >= 0');
        }

        if ($count !== null && ($count < 1 || $count > 200)) {
            throw new \InvalidArgumentException('Count must be between 1 and 200');
        }

        $this->workspace = $workspace;
        $this->page = $page;
        $this->count = $count;
    }

    public function toArray(): array
    {
        return array_filter([
            'workspace'  => $this->workspace,
            'page'  => $this->page,
            'count' => $this->count
        ], fn($v) => $v !== null);
    }
}
