<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class vaultUpload extends Command
{
    protected $signature = 'app:vault-upload {keyFilePath} {certNumber}';
    protected $description = 'Uploads the existing private key to vault, replacing if a key already exists';

    private Client $client;
    private $vaultToken;
    private $vaultAddress;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client(['verify' => false]); // Disable SSL verification
        $this->vaultAddress = env('VAULT_ADDRESS');
    }

    public function handle(): int
    {
        $certNumber = $this->argument('certNumber');
        $keyFilePath = $this->argument('keyFilePath');

        if (!file_exists($keyFilePath)) {
            $this->error("Key file does not exist.");
            return 1;
        }

        try {
            $this->authenticateWithVault();

            if ($this->keyExists($certNumber)) {
                $this->deleteExistingKey($certNumber);
            }

            $this->uploadKeyToVault($certNumber, $keyFilePath);

            $this->info("Successfully uploaded the private key to Vault.");
            return 0; // Success
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return $this->getExitCode($e);
        }
    }

    private function authenticateWithVault(): void
    {
        $username = env('VAULT_USERNAME');
        $password = env('VAULT_PASSWORD');

        $response = $this->client->post("{$this->vaultAddress}/v1/auth/userpass/login/$username", [
            'json' => ['password' => $password],
        ]);

        $data = json_decode($response->getBody(), true);
        $this->vaultToken = $data['auth']['client_token'];
    }

    private function keyExists($certNumber): bool
    {
        try {
            $this->client->get("{$this->vaultAddress}/v1/onelink-certs/$certNumber", [
                'headers' => ['X-Vault-Token' => $this->vaultToken],
            ]);
            return true;
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * @throws GuzzleException
     */
    private function deleteExistingKey($certNumber): void
    {
        $this->client->delete("{$this->vaultAddress}/v1/onelink-certs/$certNumber", [
            'headers' => ['X-Vault-Token' => $this->vaultToken],
        ]);
        $this->info("Existing key deleted successfully.");
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    private function uploadKeyToVault($certNumber, $keyFilePath): void
    {
        $keyContent = file_get_contents($keyFilePath);
        $response = $this->client->post("{$this->vaultAddress}/v1/onelink-certs/$certNumber", [
            'headers' => ['X-Vault-Token' => $this->vaultToken],
            'json' => [
                'value' => $keyContent,
            ],
        ]);

        if (!in_array($response->getStatusCode(), [200, 204])) {
            throw new \Exception("Failed to upload key to Vault. Status code: " . $response->getStatusCode());
        }
    }

    private function getExitCode(\Exception $e): int
    {
        $errorMap = [
            'Failed to authenticate with Vault' => 2,
            'Failed to check existing key' => 3,
            'Failed to delete existing key' => 4,
            'Failed to upload key to Vault' => 5,
        ];

        foreach ($errorMap as $errorMessage => $exitCode) {
            if (str_contains($e->getMessage(), $errorMessage)) {
                return $exitCode;
            }
        }

        return 1; // General error
    }

    public static function getErrorMessage($exitCode): string
    {
        $messages = [
            0 => 'Success',
            1 => 'Key file does not exist or an unknown error occurred.',
            2 => 'Failed to authenticate with Vault.',
            3 => 'Failed to check for existing key in Vault.',
            4 => 'Failed to delete existing key from Vault.',
            5 => 'Failed to upload key to Vault.',
        ];
        return $messages[$exitCode] ?? 'An unknown error occurred.';
    }
}
