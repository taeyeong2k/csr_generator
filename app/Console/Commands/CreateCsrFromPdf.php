<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use mikehaertl\pdftk\Pdf;

class CreateCsrFromPdf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-csr-from-pdf {pdfFilePath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CSR data from a PDF file';

    private array $fieldMappings = [
        'Your Valid Country Name (2 letter code)' => 'country',
        'Your Valid State or Province Name spelled out' => 'state',
        'Your Valid City Name (spelled out)' => 'city',
        'Your Valid Organization (Corporation) Name' => 'organization',
        'Your Valid domain name' => 'domains',
        'Your Valid Email Address' => 'email',
        'Your Valid Department Name (optional)' => 'department'
    ];

    /**
     * Execute the console command.
     * @throws \Exception
     */
    public function handle()
    {
        $pdfFilePath = $this->argument('pdfFilePath');
        $pdf = new Pdf($pdfFilePath);
        $data = $pdf->getDataFields();
        $arr = $data->__toArray();

        // Parse the array and get the relevant field values
        $parsedData = json_encode($this->parseArray($arr));

        // Output the parsed data as JSON
        $this->info($parsedData);
        // dd($parsedData);
        return $parsedData;
    }

    private function parseArray($array)
    {
        $result = [
            'country' => '',
            'state' => '',
            'city' => '',
            'organization' => '',
            'domains' => [],
            'email' => '',
            'department' => ''
        ];

        foreach ($array as $field) {
            if (isset($field['FieldName']) && isset($field['FieldValue'])) {
                $fieldName = $field['FieldName'];
                $value = $field['FieldValue'];

                if (isset($this->fieldMappings[$fieldName])) {
                    $key = $this->fieldMappings[$fieldName];

                    if ($key == 'country') {
                        $result[$key] = strtoupper($value);
                    } elseif ($key == 'domains') {
                        $result[$key] = $this->extractDomains($value);
                    } else {
                        $result[$key] = $value;
                    }
                }
            }
        }

        return $result;
    }

    private function extractDomains($value)
    {
        $lines = preg_split('/[\n,\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        $domains = [];

        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('#^https?://#', '', $line);
            $line = rtrim($line, '/');

            $parsedUrl = parse_url($line);
            $domain = $parsedUrl['host'] ?? $parsedUrl['path'] ?? $line;

            if ($this->isValidDomain($domain) && !in_array($domain, $domains)) {
                $domains[] = $domain;
            }
        }

        return $domains;
    }

    private function isValidDomain($domain): false|int
    {
        return preg_match('/^(?:\*\.)?(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $domain);
    }

}
