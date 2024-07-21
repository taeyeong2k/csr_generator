<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;

class CheckSite extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-site {site}';

    /**
     * Returns info of SSL certificate of provided site (assumes URL has been parsed)
     *
     * @var string
     */
    protected $description = 'Check SSL information of a site';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $site = $this->argument('site');
            // Strip leading wildcard characters, http, https, and leading dot if preceded by *
            $site = preg_replace('/^(\*\.|https?:\/\/|\*)/', '', $site);

            $get = stream_context_create(array("ssl" => array("capture_peer_cert" => TRUE)));
            $read = @stream_socket_client("ssl://".$site.":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $get);

            if ($read === false) {
                throw new Exception("Failed to connect to the site: $errstr ($errno)");
            }

            $cert = stream_context_get_params($read);

            if (!isset($cert['options']['ssl']['peer_certificate'])) {
                throw new Exception("Failed to retrieve SSL certificate");
            }

            $cert_info = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);

            if ($cert_info === false) {
                $error = openssl_error_string();
                throw new Exception("Failed to parse SSL certificate: $error");
            }

            $parsed_output = $this->parse_output($cert_info);
            $parsed_output["checked_domain"] = $site;
            $this->info(json_encode($parsed_output));
            return $parsed_output;
        } catch (Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return null;
        }
    }

    private function parse_output(Array $arr) {
        $result = [];
        // Expiry
        $valid_to = date_create_from_format('ymdHise', $arr['validTo'])->format('Y-m-d');
        $domains = $arr['extensions']['subjectAltName'];
        $common_name = $arr['subject']['CN'];

        // Ensure $domains is an array
        if (is_string($domains)) {
            $domains = explode(',', $domains); // Assuming the string is comma-separated
        }

        // Print the original domains array
        // print_r($domains);

        // Strip "DNS:" from each domain
        $clean_domains = array_map(function($domain) {
            return trim(str_replace('DNS:', '', $domain));
        }, $domains);

        // Print the cleaned domains array
        // print_r($clean_domains);

        $result['valid_to'] = $valid_to;
        $result['common_name'] = $common_name;
        $result['domains'] = $clean_domains;
        return $result;
    }
}
