<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Request\Projects;

class ExportProjectLeadsRequest
{
    private int $projectId;
    private string $format;

    public function __construct(int $projectId, string $format = 'csv')
    {
        if (!in_array($format, ['csv', 'xls'], true)) {
            throw new \InvalidArgumentException('Format must be "csv" or "xls".');
        }

        $this->projectId = $projectId;
        $this->format = $format;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function toArray(): array
    {
        return [
            'format' => $this->format
        ];
    }
}
