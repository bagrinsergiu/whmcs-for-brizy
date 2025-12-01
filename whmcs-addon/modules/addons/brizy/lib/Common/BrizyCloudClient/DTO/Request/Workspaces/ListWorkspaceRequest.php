<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Workspaces;

class ListWorkspaceRequest
{
    /**
     * @var int|null
     */
    private $page;

    /**
     * @var int|null
     */
    private $count;

    public function __construct(?int $page = null, ?int $count = null)
    {
        if ($page !== null && $page < 0) {
            throw new \InvalidArgumentException('Page must be >= 0');
        }

        if ($count !== null && ($count < 1 || $count > 200)) {
            throw new \InvalidArgumentException('Count must be between 1 and 200');
        }

        $this->page = $page;
        $this->count = $count;
    }

    public function toArray(): array
    {
        return array_filter([
            'page'  => $this->page,
            'count' => $this->count
        ], fn($v) => $v !== null);
    }
}
