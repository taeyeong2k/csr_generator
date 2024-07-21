<?php

namespace App\Http\Controllers;

use App\Providers\CSRConfig\CSRConfig;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

abstract class Controller
{
    // Creates a new temp .conf file and stores the file path in vault
    // Retrieve with $this->>getConfFilePath();
    /**
     * @param $confArray
     */
    public function confirmView($confArray)
    {
        $domain = $confArray["Domains"][0];
        // Check the first domain, if it's WC, ignore the WC
        $checkSiteResult = json_decode($this->checkDomain($domain), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // JSON decoding failed
            $existing_cert = null;
            $cert_error = "Failed to parse certificate information: " . json_last_error_msg();
        } elseif (isset($checkSiteResult['status']) && $checkSiteResult['status'] === 'error') {
            // We have a JSON object with an error status
            $existing_cert = null;
            $cert_error = $checkSiteResult['message'] ?? 'Unknown error occurred';
        } else {
            // Assume the JSON object contains the certificate information
            $existing_cert = $checkSiteResult;
            $cert_error = null;
        }
        // Go to confirmation view
        return view('csr_confirm', compact('confArray', 'existing_cert', 'cert_error'));
    }

    protected function createTempFilePath(): string {
        $tempFilePath = uniqid();
        session([
            'tempFilePath' => $tempFilePath,
        ]);
        return $tempFilePath;
    }
    protected function getConfFilePath(): string {
        return Storage::path(Session::get('tempFilePath') . '.conf');
    }
    protected function getKeyFilePath(): string {
        return Storage::path(Session::get('tempFilePath') . '.key');
    }

    protected function getCsrFilePath(): string {
        return Storage::path(Session::get('tempFilePath') . '.csr');
    }

    protected function getPdfFilePath(): string {
        return Storage::path(Session::get('tempFilePath') . '.pdf');
    }

    /**
     * @throws \Exception
     */
    protected function createCsrConfig(array $data)
    {
        $requiredKeys = ['country', 'state', 'city', 'organization', 'email', 'domains'];
        $missingKeys = array_diff($requiredKeys, array_keys($data));

        if (!empty($missingKeys)) {
            throw new \Exception('Please check that you uploaded the correct PDF. Sometimes the PDF returned by the client will not work with this site. In that case, please enter the information manually.');
        }

        return new CSRConfig(
            $data['country'],
            $data['state'],
            $data['city'],
            $data['organization'],
            $data['email'],
            $data['domains']
        );
    }

    protected function checkDomain(String $domain) {
        Artisan::call('app:check-site', ['site' => $domain]);
        return Artisan::output();
    }
}
