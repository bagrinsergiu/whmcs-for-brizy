<?php
namespace WHMCS\Module\Addon\Brizy\Common\BrizyCloudClient\Exception;

use \GuzzleHttp\Exception\GuzzleException;

class ApiException extends \RuntimeException
{
    public static function fromGuzzle(GuzzleException $e): self
    {
        $msg = $e->getMessage();
        if (method_exists($e, 'getResponse') && $e->getResponse()) {
            $msg = (string) $e->getResponse()->getBody();
        }

        return new self($msg, $e->getCode(), $e);
    }
}
