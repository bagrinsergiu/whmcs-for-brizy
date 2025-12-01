<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects;

class UpdateProjectRequest
{
    private $id;
    private $data;

    public function __construct(int $id, array $data = [])
    {
        $this->id = $id;
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function getProjectId(): int
    {
        return $this->id;
    }
}