<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects;

class DeleteProjectRequest
{
    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getProjectId(): int
    {
        return $this->id;
    }
}
