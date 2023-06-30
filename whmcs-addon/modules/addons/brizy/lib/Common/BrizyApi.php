<?php
namespace  WHMCS\Module\Addon\Brizy\Common;
use \GuzzleHttp\Client;
use WHMCS\Module\Addon\Brizy\Common\Settings;

/**
 * Brizy API
 */
Class BrizyApi {

    /**
     * Brizy API URL
     *
     * @var string
     */
    private $apiUrl = 'https://www.brizy.cloud/api/';

    /**
     * API token
     *
     * @var string
     */
    private $token;

    /**
     * Client
     *
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * Latest request
     *
     * @var \GuzzleHttp\Client
     */
    private $latestRequest;

    /**
     * Latest error
     *
     * @var mixed
     */
    private $latestError;

    /**
     * Debug mode
     *
     * @var boolean
     */
    private $debug = false;


    public function __construct()
    {
        $this->token = Settings::get('api_token');


        $this->client = new Client([
            'base_url' => $this->apiUrl,
            'base_uri' => $this->apiUrl,
            'headers' => [
                'x-auth-user-token' => $this->token,
            ]

        ]);
    }

    public function createNewLicense() {
        return $this->request('licences', 'POST');
    }

    public function getLicenses($status = 'active') {
        return $this->request('licences?status=' . $status);
    }

    public function updateLicense($license, $data) {
        return $this->request('licences/' . $license, 'PUT', $data);
    }

    public function deleteLicense($license) {
        return $this->request('licences/' . $license, 'DELETE');
    }

    public function getDemos() {
        return $this->request('https://websitebuilder-demo.net/wp-json/demos/v1/demos');
    }

    public function getLatestError() {
        return $this->latestError;
    }


    public function request($endpoint, $type = 'GET', $data = null)
    {

        try {
            $this->latestRequest = $this->client->request($type, $endpoint, [
                'json' => $data
            ]);

            $statusCode = $this->latestRequest->getStatusCode();

            if (!in_array($statusCode, [200, 201, 204])) {


                $this->setError('Unable to retrieve data - Status: ' . $statusCode);

                return false;
            }

            $body = (string)$this->latestRequest->getBody();

            // if (!$body ) {
            //     $this->setError('Unable to retrieve data - Empty response');
            //     return false;
            // }

            return $this->decodeJson($body);
        } catch (\GuzzleHttp\Exception\ConnectException $ce) {
            $this->setError('Unable to connect to API');
            return false;
        } catch (\GuzzleHttp\Exception\RequestException $e) {

            $body = (string)$e->getResponse()->getBody();
            $responseData = $this->decodeJson($body);

            if (isset($responseData->message) && trim($responseData->message)) {
                $this->setError($responseData->message);
            }

            $responseCode =  $e->getResponse()->getStatusCode();
            $this->setError('There was an error handling the API - code: ' . $responseCode);

            if ($responseCode === 401) {
                $this->setError('API Token is invalid');
            }

            return false;
        }

        return $this->decodeJson($body);
    }

    private function decodeJson($data)
    {
        if (strlen($data) == 0) {
            return $data;
        }

        $decoded = json_decode($data);

        $jsonError = json_last_error();

        if (is_null($decoded) && $jsonError == JSON_ERROR_NONE) {
            $this->setError('Could not decode JSON!');
            return false;
        }


        if ($jsonError != JSON_ERROR_NONE) {
            $error = 'Could not decode JSON! ';

            switch ($jsonError) {
                case JSON_ERROR_DEPTH:
                    $error .= 'Maximum depth exceeded!';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $error .= 'Underflow or the modes mismatch!';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $error .= 'Unexpected control character found';
                    break;
                case JSON_ERROR_SYNTAX:
                    $error .= 'Malformed JSON';
                    break;
                case JSON_ERROR_UTF8:
                    $error .= 'Malformed UTF-8 characters found!';
                    break;
                default:
                    $error .= 'Unknown error!';
                    break;
            }
            $this->setError($error);

            return false;
        }

        return $decoded;
    }

    private function setError($error)
    {
        $this->latestError = $error;

        if ($this->debug) {
            var_dump($error);
        }
    }
}