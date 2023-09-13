<?php

namespace WHMCS\Module\Addon\brizy\Api;

use WHMCS\Module\Addon\Brizy\Common\Translations;

/**
 * Default api controller
 */
class DefaultApiController
{
    /**
     * Input data
     *
     * @var array
     */
    protected $input = null;

    /**
     * Message
     *
     * @var array
     */
    protected $messages = null;

    /**
     * Http status code
     *
     * @var integer
     */
    protected $statusCode = 200;

    /**
     * Constructor
     */
    public function __construct()
    {
        Translations::set();
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);


        if (isset($data['ftp']) && is_array($data['ftp'])) {
            array_walk($data['ftp'], function (&$value) {
                if (is_string($value)) {
                    $value = trim($value);
                }
            });
        }

        $this->input = $data;
    }
    /**
     * Geter - statusCode
     *
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Geter  - messages
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Seter - statusCode
     *
     * @param integer $code
     * @return self
     */
    public function setStatusCode($code)
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Adds one message
     *
     * @param string  $message
     * @param string $type
     * @return void
     */
    public function addMessage($message, $type = 'success')
    {
        $this->messages[] = ['message' => $message, 'type' => $type];

        return $this;
    }

    /**
     * Respond with Not Found
     *
     * @param string $message
     * @return string
     */
    public function respondNotFound($message = 'Not Found!')
    {
        return $this->setStatusCode(404)->respondWithError($message);
    }

    /**
     * Respond with Internal Error
     *
     * @param string $message
     * @return string
     */
    public function respondInternalError($message = 'Internal Error!')
    {
        return $this->setStatusCode(500)->respondWithError($message);
    }

    /**
     * Respond with custom error message
     *
     * @param array $message
     * @return string
     */
    public function respondWithError($message)
    {
        $this->setStatusCode(403);
        return $this->respond([
            'error' => [
                'message' => $message,
            ]
        ], $this->getStatusCode());
    }

    /**
     * Simple JSON response
     *
     * @param array $data
     * @param array $headers
     * @return string
     */
    public function respond($data = [], $headers = [])
    {
        if (is_object($data)) {
            $data = json_decode(json_encode($data));
        }

        $response = [
            'status' => $this->getStatusCode(),
            'data' => $data,
        ];

        if ($this->getMessages()) {
            $response['messages'] = $this->getMessages();
        }

        http_response_code($this->getStatusCode());

        echo json_encode($response);
        die();
    }
}
