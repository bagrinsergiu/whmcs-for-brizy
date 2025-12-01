<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\Projects;

class LeadResponse
{
    public function __construct(int $id, private \DateTimeImmutable $createdAt, private array $formData) {}

    public static function fromArray(array $data): self
    {
        $item = $data['data'] ?? $data;

        return new self(
            (int) $item['id'],
            new \DateTimeImmutable($item['created_at']),
            (array) $item['form_data']
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getFormData(): array
    {
        return $this->formData;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->createdAt,
            'form_data' => $this->formData,
        ];
    }
}
