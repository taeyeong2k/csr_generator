<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PDFController extends Controller
{
    public function parsePdf(Request $request)
    {
        try {
            $request->validate([
                'pdf_file' => 'required|file|mimes:pdf|max:10240',
            ]);

            $tempFilePath = $this->createTempFilePath();
            $pdfFilePath = $this->createPdfFile($request, $tempFilePath);

            Artisan::call('app:create-csr-from-pdf', [
                'pdfFilePath' => $pdfFilePath
            ]);

            $output = Artisan::output();
            $array = json_decode($output, true);

            if (!is_array($array)) {
                throw new \Exception('Invalid JSON output from PDF parsing');
            }

            $config = $this->createCsrConfig($array);
            $confArray = $config->getConfig();

            return Controller::confirmView($confArray);

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'PDF Upload failed: ' . $e->getMessage()]);
        }
    }

    /**
     * @param Request $request
     * @return false|string
     */
    public function createPdfFile(Request $request, String $tempFilePath): string|false
    {
        $pdfFile = $request->file('pdf_file');
        $pdfFileName = $tempFilePath . '.pdf';

        // Store the file in the root of the storage path
        Storage::putFileAs('', $pdfFile, $pdfFileName);
        return Storage::path($pdfFileName);
    }

}
