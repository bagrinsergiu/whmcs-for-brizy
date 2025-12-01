<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Sso;

use WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Sso\Multipass;

class SsoManager
{
    private string $partnerId;
    private string $secretKey;
    private string $whiteLabelDomain;

    public function __construct(string $partnerId, string $secretKey, string $whiteLabelDomain)
    {
        $this->partnerId = $partnerId;
        $this->secretKey = $secretKey;
        $this->whiteLabelDomain = rtrim($whiteLabelDomain, '/') . '/';
    }

    /**
     * Build a Multipass token for the given project and user.
     *
     * @param string $projectId ID of the project to open
     * @param array  $userData  Associative array of user info.
     *
     * @return string URL-safe Multipass token
     */
    private function generateToken(string $projectId, array $userData): string
    {
        $payload = array_merge([
            'partner_id' => $this->partnerId,
            'project_uid' => (string) $projectId,
            'created_at' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM)
        ], $userData);

        $mp = new Multipass($this->secretKey);

        return $mp->encode($payload);
    }

    /**
     * Get the full redirect URL to launch the editor via Multipass SSO.
     *
     * @param string $projectId ID of the project
     * @param array  $userData  User data array for token (see generateToken)
     * @param string $lang      Language code
     *
     * @return string URL to redirect user to
     */
    public function getRedirectUrl(string $projectUId, array $userData, string $lang = 'en'): string
    {

        $token = $this->generateToken($projectUId, $userData);

        // format: https://{domain}/multipass/{partnerId}?token={token}&_lang={lang}
        $url = sprintf(
            '%smultipass/%s?token=%s&_lang=%s',
            $this->whiteLabelDomain,
            $this->partnerId,
            urlencode($token),
            $lang
        );

        return $url;
    }

    public function getRedirectUrlAi(int $projectId, int $workspaceId, array $userData, string $lang = 'en') {
        $payload = array_merge([
            'created_at' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'email' => $userData['email'],
            'project_uid' => (string) $projectId,
            'builder_type' => 'ai',
            'workspace_id' => $workspaceId,
        ]);

        $mp = new Multipass($this->secretKey);

        $token = $mp->encode($payload);


        // format: https://{domain}/multipass/{partnerId}?token={token}&_lang={lang}
        $url = sprintf(
            '%smultipass/%s?token=%s&_lang=%s',
            $this->whiteLabelDomain,
            $this->partnerId,
            urlencode($token),
            $lang
        );

        return $url;
    }

    public function getAiProjectRedirectUrl(string $projectUId, $lang = 'en'){
        $url = sprintf(
            '%sproject/view/%s?_lang=%s',
            $this->whiteLabelDomain,
            $projectUId,
            $lang
        );

        return $url;
    }
}
