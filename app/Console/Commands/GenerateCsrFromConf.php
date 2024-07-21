<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GenerateCsrFromConf extends Command
{
    protected $signature = 'app:generate-csr-from-conf {tempFilePath} {use4096BitKey}';
    protected $description = 'Generate CSR from the provided configuration file';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $tempFilePath = Storage::path($this->argument('tempFilePath'));
        $confFilePath = $tempFilePath.'.conf';
        $confContent = file_get_contents($confFilePath);

        // Process the configuration content as needed
        if ($confContent === false) {
            $this->error('Failed to read the configuration file.');
            return;
        }
        // For now, just print the content
        $this->info('Configuration content:');
        $this->line($confContent);

        $keyFilePath = Storage::path($this->argument('tempFilePath')) . '.key';
        $csrFilePath = Storage::path($this->argument('tempFilePath')) . '.csr';

        $use4096BitKey = $this->argument('use4096BitKey');
        $keySize = $use4096BitKey ? 4096 : 2048;

        $command = "openssl req -new -sha256 -batch -nodes -newkey rsa:{$keySize} -keyout {$keyFilePath} -out {$csrFilePath} -config {$confFilePath}";

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->info('CSR generation completed successfully');
        return 0;
    }
}
