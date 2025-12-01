<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\Workspaces;

class CountResponse
{
    private int $count;

    public function __construct(int $count)
    {
        $this->count = $count;
    }

    public static function fromArray(array $data): self
    {
        return new self($data['count']);
    }

    public function getCount(): int
    {
        return $this->count;
    }
}