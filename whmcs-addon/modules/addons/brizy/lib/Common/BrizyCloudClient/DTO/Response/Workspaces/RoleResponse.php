<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\Workspaces;

class RoleResponse
{
    private string $slug;
    private string $name;

    public function __construct(string $slug, string $name)
    {
        $this->slug = $slug;
        $this->name = $name;
    }

    public static function fromArray(?array $data): self
    {
        if (!is_array($data)) {
            return new self('', '');
        }

        return new self(
            $data['slug'] ?? '',
            $data['name'] ?? ''
        );
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name
        ];
    }
}
