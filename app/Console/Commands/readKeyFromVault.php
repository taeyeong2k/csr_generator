<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class readKeyFromVault extends Command
{
    protected $signature = 'app:read-key-from-vault {certNumber}';
    protected $description = 'Reads a value from Vault using the provided certNumber';

    public function handle()
    {
        $certNumber = $this->argument('certNumber');
        $username = env('VAULT_USERNAME');
        $password = env('VAULT_PASSWORD');
        $vaultAddress = env('VAULT_ADDRESS');

        $client = new Client();

        // Authenticate with Vault
        try {
            $response = $client->post("$vaultAddress/v1/auth/userpass/login/$username", [
                'json' => ['password' => $password],
                'verify' => false, // Disable SSL verification
            ]);
            $data = json_decode($response->getBody(), true);
            $vaultToken = $data['auth']['client_token'];
        } catch (\Exception $e) {
            Log::error("Failed to authenticate with Vault: " . $e->getMessage());
            return 2; // Failed to authenticate with Vault
        }

        // Check if something already exists with that ticket number
        try {
            $response = $client->get("$vaultAddress/v1/onelink-certs/$certNumber", [
                'headers' => ['X-Vault-Token' => $vaultToken],
                'verify' => false, // Disable SSL verification
            ]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody(), true);
                if (isset($data['data']['value'])) {
                    $key = $data['data']['value'];
                    $this->line($key);
                    return 0; // Success
                } else {
                    $this->warn("Key not found in the response for certificate number $certNumber.");
                    return 4; // Key not found in response
                }
            }
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() == 404) {
                $this->info("No key found for certificate number $certNumber.");
                return 1; // Key not found
            } else {
                Log::error("Failed to check existing key in Vault: " . $e->getMessage());
                return 5; // Failed to check existing key
            }
        }
    }
}
