<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects;

class GetProjectRequest
{
    public $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getProjectId(): int
    {
        return $this->id;
    }
}