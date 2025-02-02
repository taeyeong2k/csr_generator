<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;

class GenerateSlackMessage extends Command
{
    protected $signature = 'app:generate-slack-message {csr} {cert_number} {name} {pdf_file_path?}';

    protected $description = 'Generate and send a Slack message with CSR details and optional PDF attachment';

    private $slackToken;
    private $slackChannel;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->slackToken = env('SLACK_BOT_TOKEN');
        if (!$this->slackToken) {
            throw new \Exception('SLACK_BOT_TOKEN is not set in the .env file');
        }
        $this->slackChannel = env('SLACK_CSR_CHANNEL', 'onelink-devops-certs');
    }
    public function handle()
    {
        $csr = $this->argument('csr');
        $cert_number = $this->argument('cert_number');
        $name = $this->argument('name');
        $pdf_file_path = $this->argument('pdf_file_path');

        try {
            $message = $this->formatMessage($csr, $cert_number, $name);
            dd($message);

            $uploadedFileInfo = null;
            if ($pdf_file_path && file_exists($pdf_file_path)) {
                $uploadedFileInfo = $this->uploadFile($pdf_file_path);
            }

            $this->sendMessage($message, $uploadedFileInfo);

            $this->info('Slack message sent successfully.');
        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }

    private function formatMessage($csr, $cert_number, $name)
    {
        // Strip non numbers from the end of cert number for url
        $url_cert_number = preg_replace('/\D+$/', '', $cert_number);
        return <<<EOD
CSR has been generated for ticket: https://rt.transperfect.com/Ticket/Display.html?id=$url_cert_number
Vault key: onelink-certs/CERT-{$cert_number}
```
{$csr}```
Generated by: $name
EOD;
    }

    private function uploadFile($file_path)
    {
        // Step 1: Get upload URL
        $response = Http::withToken($this->slackToken)
            ->asForm()
            ->post('https://slack.com/api/files.getUploadURLExternal', [
                'filename' => basename($file_path),
                'length' => filesize($file_path),
            ]);

        if (!$response->successful() || !$response->json('ok')) {
            throw new \Exception('Failed to get upload URL: ' . ($response->json('error') ?? 'Unknown error'));
        }

        $uploadUrl = $response->json('upload_url');
        $fileId = $response->json('file_id');

        // Step 2: Upload file
        $response = Http::attach('file', file_get_contents($file_path), basename($file_path))
            ->post($uploadUrl);

        if (!$response->successful()) {
            throw new \Exception('Failed to upload file: ' . $response->body());
        }

        // Step 3: Complete upload
        $response = Http::withToken($this->slackToken)
            ->asForm()
            ->post('https://slack.com/api/files.completeUploadExternal', [
                'files' => json_encode([
                    [
                        'id' => $fileId,
                        'title' => 'CSR Generation PDF',
                    ]
                ]),
                'channel_id' => $this->slackChannel,
            ]);

        if (!$response->successful() || !$response->json('ok')) {
            throw new \Exception('Failed to complete upload: ' . ($response->json('error') ?? 'Unknown error'));
        }

        return $response->json('files')[0];
    }

    /**
     * @throws ConnectionException
     */
    private function sendMessage($message, $fileInfo = null)
    {
        $params = [
            'channel' => $this->slackChannel,
            'text' => $message,
        ];

        if ($fileInfo) {
            $params['attachments'] = json_encode([
                [
                    'text' => 'PDF used for CSR Generation',
                    'file_id' => $fileInfo['id'],
                ]
            ]);
        }

        $response = Http::withToken($this->slackToken)->post('https://slack.com/api/chat.postMessage', $params);

        if (!$response->successful() || !$response->json('ok')) {
            throw new \Exception('Failed to send message: ' . ($response->json('error') ?? 'Unknown error'));
        }
    }
}
