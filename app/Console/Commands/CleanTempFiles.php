<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class CleanTempFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-temp-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete temp files older than 2 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the temporary directory path
        $tempFilesPath = storage_path('app');
        $files = File::files($tempFilesPath);
        $now = Carbon::now();

        // Iterate through the files and delete those older than 2 hours
        foreach ($files as $file) {
            $fileModifiedTime = Carbon::createFromTimestamp($file->getMTime());

            // Check if the file is older than 2 hours
            if ($fileModifiedTime->lt($now->subHours(2))) {
                $this->info('Deleting temp file: ' . $file->getFilename());
                File::delete($file->getRealPath());
            }
        }
    }
}
