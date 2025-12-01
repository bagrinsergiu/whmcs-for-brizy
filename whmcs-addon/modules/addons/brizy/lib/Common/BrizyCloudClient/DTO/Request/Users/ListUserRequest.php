<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Users;

class ListUserRequest
{
    private ?int $page;
    private ?int $count;
    private ?string $email;

    public function __construct(?int $page = null, ?int $count = null, ?string $email = null)
    {
        if ($page !== null && $page < 0) {
            throw new \InvalidArgumentException('Page must be >= 0');
        }

        if ($count !== null && ($count < 1 || $count > 200)) {
            throw new \InvalidArgumentException('Count must be between 1 and 200');
        }

        $this->page = $page;
        $this->count = $count;
        $this->email = $email;
    }

    public function toArray(): array
    {
        return array_filter([
            'page'  => $this->page,
            'count' => $this->count,
            'email' => $this->email
        ], fn($v) => $v !== null);
    }
}
