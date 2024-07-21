<?php

namespace App\Http\Controllers;

use App\Console\Commands\vaultUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class CSRController extends Controller
{
    /**
     * @throws \Exception
     */
    public function parseConf(Request $request)
    {
        $data = [
            'country' => $request->input('country'),
            'state' => $request->input('state'),
            'city' => $request->input('city'),
            'organization' => $request->input('organization'),
            'email' => $request->input('email'),
            'domains' => $request->input('domain_name'),
        ];

        $config = $this->createCsrConfig($data);
        $confArray = $config->getConfig();

        $this->createTempFilePath();
        return Controller::confirmView($confArray);
    }

    public function generateFinalCsr(Request $request) {
        $data = [
            'country' => $request->input('Country'),
            'state' => $request->input('State'),
            'city' => $request->input('City'),
            'organization' => $request->input('Organization'),
            'email' => $request->input('Email'),
            'domains' => $request->input('domains'),
        ];
        $use4096BitKey = $request->input('key_size') == "on";

        $cert_number = $request->input('cert_number');
        $tempFilePath = session('tempFilePath');

        $name = $request->input('name');

        // Generate CSR
        $this->handleCsrGeneration($data, $tempFilePath, $use4096BitKey);

        // Upload to Vault
        return $this->uploadCSR($cert_number, $data, $name);

    }

    private function handleCsrGeneration($data, $tempFilePath, $use4096BitKey)
    {
        $confContent = $this->generateConf(
            $data['country'],
            $data['state'],
            $data['city'],
            $data['organization'],
            $data['email'],
            $data['domains']
        );

        // Write the configuration content to a temporary file
        Storage::put($tempFilePath . '.conf', $confContent);

        // Generate conf file
        Artisan::call('app:generate-csr-from-conf', [
            'tempFilePath' => Session::get('tempFilePath'),
            'use4096BitKey' => $use4096BitKey,
        ]);
    }


    // Private function to copy form contents to template
    private function generateConf($country, $state, $city, $org, $email, $domains)
    {
        // Read the template file
        $templatePath = resource_path('templates/csr_template.conf');
        $template = file_get_contents($templatePath);

        // Prepare alt names list
        $altNamesList = "";
        foreach ($domains as $index => $domain) {
            $altNamesList .= "DNS.$index = $domain\n";
        }

        // Replace placeholders with actual values
        return str_replace(
            ['{Country}', '{State}', '{Location}', '{Organization}', '{Org_Unit}', '{Email}', '{CN}', '{alt_names_list}'],
            [$country, $state, $city, $org, '', $email, $domains[0], $altNamesList],
            $template
        );
    }

    // Upload CSR
    public function uploadCSR(string $cert_number, array $data, string $name)
    {
        $vault_key = 'CERT-' . $cert_number;
        $keyFilePath = $this->getKeyFilePath();
        $csrFilePath = $this->getCsrFilePath();
        $pdfFilePath = $this->getPdfFilePath();
        $pdfExists = File::exists($pdfFilePath);
        $csrContent = file_get_contents($csrFilePath);

        $exit_code = Artisan::call('app:vault-upload', [
            'keyFilePath' => $keyFilePath,
            'certNumber' => $vault_key,
        ]);

        Artisan::call('app:generate-slack-message', [
            'csr' => $csrContent,
            'cert_number' => $cert_number,
            'name' => $name,
            'pdf_file_path' => $pdfExists ? $pdfFilePath : null,
        ]);

        if ($exit_code == 0 || $exit_code == 3) {
            return $this->showSuccessPage($data, $cert_number);
        } else {
            return $this->showFailurePage($exit_code, $data, $cert_number);
        }
    }

    public function showSuccessPage(array $data, string $cert_number)
    {
        $csrFilePath = $this->getCsrFilePath();
        $csrContent = file_get_contents($csrFilePath);
        $confArray = $data;

        return view('csr_success', compact('confArray', 'csrContent', 'cert_number'));
    }

    public function showFailurePage(int $exit_code, array $data, string $cert_number)
    {
        $errorMessage = vaultUpload::getErrorMessage($exit_code);
        $csrContent = file_get_contents($this->getCsrFilePath());
        $privateKey = file_get_contents($this->getKeyFilePath());
        $confArray = $data;
        return view('csr_error', compact('errorMessage', 'confArray', 'csrContent', 'privateKey', 'cert_number'));
    }
}
