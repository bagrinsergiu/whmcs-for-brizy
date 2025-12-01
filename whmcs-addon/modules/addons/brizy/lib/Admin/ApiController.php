<?php

namespace WHMCS\Module\Addon\Brizy\Admin;

use WHMCS\Module\Addon\brizy\Api\DefaultApiController;
use WHMCS\Database\Capsule;
use WHMCS\User\Client;
use WHMCS\Service\Service;
use WHMCS\Module\Addon\Brizy\Common\BrizyApi;
use WHMCS\Module\Addon\Brizy\Common\Helpers;

/**
 * Admin area API controller
 */
class ApiController extends DefaultApiController {

    /**
     * Brizy API
     *
     * @var BrizyApi
     */
    private $brizyApi;
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->brizyApi = new BrizyApi();
    }

    /**
     * Returns all licenses
     *
     * @return void
     */
    public function getAllLicenses() {

        Helpers::synchronizeAllLicenses();

        $licenses = Capsule::table('brizy_licenses')
            ->orderBy('id', 'desc')
            ->get();

        $licensesData = [];
        foreach($licenses as $license) {
            $licensesData[] = $this->prepareLicenseData($license);
        }

        $this->respond($licensesData);
    }

    /**
     * Delete license
     *
     * @return void
     */
    public function deleteLicense() {

        $licenseId = (int)$_GET['license_id'];

        if ($licenseId) {

            $licenseDb = Capsule::table('brizy_licenses')
            ->where('id', $licenseId)
            ->first();


            if ($licenseDb) {
                $response = $this->brizyApi->deleteLicense($licenseDb->license);
                Capsule::table('brizy_licenses')
                    ->where('id', $licenseDb->id)
                    ->delete();

                $this->respond();
            }

            $this->respond();
        }

        $this->respondWithError('Unable to find license with id: '.$licenseId);
    }

    /**
     * Revoke license
     *
     * @return void
     */
    public function revokeLicense() {

        $licenseId = (int)$_GET['license_id'];

        if ($licenseId) {
            $updatedCount = Capsule::table('brizy_licenses')
            ->where('id', $licenseId)
            ->update([
                'user_id' => 0,
                'service_id' => 0,
            ]);

            $this->respond();
        }

        $this->respondWithError('Unable to find license with id: '.$licenseId);
    }

    /**
     * Disable license
     *
     * @return void
     */
    public function disableLicense() {

        $licenseId = (int)$_GET['license_id'];

        if ($licenseId) {
            $licenseDb = Capsule::table('brizy_licenses')
            ->where('id', $licenseId)
            ->first();

            if ($licenseDb) {
                $response = $this->brizyApi->updateLicense($licenseDb->license, ['status' => 'non-active', 'domain' => $licenseDb->activation_domain]);
                $this->respond();
            }
        }

        $this->respondWithError('Unable to find license with id: ' . $licenseId);
    }

    /**
     * Disable license
     *
     * @return void
     */
    public function activateLicense() {

        $licenseId = (int)$_GET['license_id'];

        if ($licenseId) {
            $licenseDb = Capsule::table('brizy_licenses')
            ->where('id', $licenseId)
            ->first();

            if ($licenseDb) {
                $response = $this->brizyApi->updateLicense($licenseDb->license, ['status' => 'active', 'domain' => $licenseDb->activation_domain]);
                $this->respond();
            }
        }

        $this->respondWithError('Unable to find license with id: ' . $licenseId);
    }


    /**
     * Adds a license
     *
     * @return void
     */
    public function addLicense() {

        $response = $this->brizyApi->createNewLicense();

        if ($response && isset($response->license)) {
            $licenses = $response->license;
            $licenses = preg_split("/[;,\r\n]+/", $licenses);
            foreach ($licenses as $license) {
                $license = trim($license);
                if ($license === '') {
                    continue;
                }

                Capsule::table('brizy_licenses')
                ->insert(
                    [
                        'license' => $license,
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                );
            }


            $this->respond();
        } else {
            $this->respondWithError($this->brizyApi->getLatestError());
        }

        $this->respondWithError('The specified license is not valid');
    }

    /**
     * Prepares additional data for the selected license
     *
     * @param \stdObject $license
     * @return void
     */
    private function prepareLicenseData($license) {
        $licenseData = (array)$license;
        if ($license->user_id) {
            $client = Client::where('id', $license->user_id)->first();
            $license->clientData = null;
            if ($client) {
                $license->clientData = $client;
            }
        }

        if ($license->service_id) {
            $service = Service::where('id', $license->service_id)->first();

            $license->serviceData = null;
            if ($service) {
                $license->serviceData = $service;
                $license->serviceData->product = $service->product;
            }
        }

        return $license;
    }
}
