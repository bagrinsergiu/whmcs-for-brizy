<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\DTO\Response\Projects;

class ProjectResponse
{
    private $workspace;
    private $deploymentStatus;
    private $status;
    private $translationVersion;
    private $id;
    private $container;
    private $name;
    private $data;
    private $siteId;
    private $uid;
    private $siteTitle;
    private $siteDescription;
    private $codeInjectionFooter;
    private $codeInjectionHeader;
    private $socialStatusMessage;
    private $socialDescription;
    private $customCss;
    private $isUsingSync;
    private $redirects;
    private $indexItemId;
    private $isPro;
    private $canonicalDomain;
    private $metadata;
    private $systemCodeInjectionFooter;
    private $systemCodeInjectionHeader;
    private $dataVersion;

    public function __construct(
        $workspace,
        $deploymentStatus,
        $status,
        $translationVersion,
        $id,
        $container,
        $name,
        $data,
        $siteId,
        $uid,
        $siteTitle,
        $siteDescription,
        $codeInjectionFooter,
        $codeInjectionHeader,
        $socialStatusMessage,
        $socialDescription,
        $customCss,
        $isUsingSync,
        $redirects,
        $indexItemId,
        $isPro,
        $canonicalDomain,
        $metadata,
        $systemCodeInjectionFooter,
        $systemCodeInjectionHeader,
        $dataVersion
    ) {
        $this->workspace = $workspace;
        $this->deploymentStatus = $deploymentStatus;
        $this->status = $status;
        $this->translationVersion = $translationVersion;
        $this->id = $id;
        $this->container = $container;
        $this->name = $name;
        $this->data = $data;
        $this->siteId = $siteId;
        $this->uid = $uid;
        $this->siteTitle = $siteTitle;
        $this->siteDescription = $siteDescription;
        $this->codeInjectionFooter = $codeInjectionFooter;
        $this->codeInjectionHeader = $codeInjectionHeader;
        $this->socialStatusMessage = $socialStatusMessage;
        $this->socialDescription = $socialDescription;
        $this->customCss = $customCss;
        $this->isUsingSync = $isUsingSync;
        $this->redirects = $redirects;
        $this->indexItemId = $indexItemId;
        $this->isPro = $isPro;
        $this->canonicalDomain = $canonicalDomain;
        $this->metadata = $metadata;
        $this->systemCodeInjectionFooter = $systemCodeInjectionFooter;
        $this->systemCodeInjectionHeader = $systemCodeInjectionHeader;
        $this->dataVersion = $dataVersion;
    }

    public static function fromArray(array $data): self
    {
        $item = $data['data'] ?? $data;

        return new self(
            (int) ($item['workspace'] ?? 0),
            $item['deployment_status'] ?? '',
            $item['status'] ?? '',
            (int) ($item['translation_version'] ?? 0),
            (int) ($item['id'] ?? 0),
            (int) ($item['container'] ?? 0),
            $item['name'] ?? '',
            $item['data'] ?? '',
            (int) ($item['site_id'] ?? 0),
            $item['uid'] ?? '',
            $item['site_title'] ?? '',
            $item['site_description'] ?? '',
            $item['code_injection_footer'] ?? '',
            $item['code_injection_header'] ?? '',
            $item['social_status_message'] ?? '',
            $item['social_description'] ?? '',
            $item['custom_css'] ?? '',
            $item['is_using_sync'] ?? false,
            $item['redirects'] ?? '',
            (int) ($item['index_item_id'] ?? 0),
            (bool) ($item['is_pro'] ?? false),
            $item['canonical_domain'] ?? '',
            $item['metadata'] ?? '',
            $item['system_code_injection_footer'] ?? '',
            $item['system_code_injection_header'] ?? '',
            (int) ($item['dataVersion'] ?? 0)
        );
    }

    public function getWorkspace(): int
    {
        return $this->workspace;
    }

    public function getDeploymentStatus(): string
    {
        return $this->deploymentStatus;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTranslationVersion(): int
    {
        return $this->translationVersion;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getContainer(): int
    {
        return $this->container;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getSiteId(): int
    {
        return $this->siteId;
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function getSiteTitle(): string
    {
        return $this->siteTitle;
    }

    public function getSiteDescription(): string
    {
        return $this->siteDescription;
    }

    public function getCodeInjectionFooter(): string
    {
        return $this->codeInjectionFooter;
    }

    public function getCodeInjectionHeader(): string
    {
        return $this->codeInjectionHeader;
    }

    public function getSocialStatusMessage(): string
    {
        return $this->socialStatusMessage;
    }

    public function getSocialDescription(): string
    {
        return $this->socialDescription;
    }

    public function getCustomCss(): string
    {
        return $this->customCss;
    }

    public function getIsUsingSync(): string
    {
        return $this->isUsingSync;
    }

    public function getRedirects(): string
    {
        return $this->redirects;
    }

    public function getIndexItemId(): string
    {
        return $this->indexItemId;
    }

    public function getIsPro(): bool
    {
        return $this->isPro;
    }

    public function getCanonicalDomain(): string
    {
        return $this->canonicalDomain;
    }

    public function getMetadata(): string
    {
        return $this->metadata;
    }

    public function getSystemCodeInjectionFooter(): string
    {
        return $this->systemCodeInjectionFooter;
    }

    public function getSystemCodeInjectionHeader(): string
    {
        return $this->systemCodeInjectionHeader;
    }

    public function getDataVersion(): int
    {
        return $this->dataVersion;
    }

    public function toArray(): array
    {
        return [
            'workspace' => $this->workspace,
            'deployment_status' => $this->deploymentStatus,
            'status' => $this->status,
            'translation_version' => $this->translationVersion,
            'id' => $this->id,
            'container' => $this->container,
            'name' => $this->name,
            'data' => $this->data,
            'site_id' => $this->siteId,
            'uid' => $this->uid,
            'site_title' => $this->siteTitle,
            'site_description' => $this->siteDescription,
            'code_injection_footer' => $this->codeInjectionFooter,
            'code_injection_header' => $this->codeInjectionHeader,
            'social_status_message' => $this->socialStatusMessage,
            'social_description' => $this->socialDescription,
            'custom_css' => $this->customCss,
            'is_using_sync' => $this->isUsingSync,
            'redirects' => $this->redirects,
            'index_item_id' => $this->indexItemId,
            'is_pro' => $this->isPro,
            'canonical_domain' => $this->canonicalDomain,
            'metadata' => $this->metadata,
            'system_code_injection_footer' => $this->systemCodeInjectionFooter,
            'system_code_injection_header' => $this->systemCodeInjectionHeader,
            'data_version' => $this->dataVersion
        ];
    }
}
