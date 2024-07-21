<?php

use App\Http\Controllers\CSRController;
use App\Http\Controllers\PDFController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('csr_generator');
});
Route::post('/generate-csr', [CSRController::class, 'parseConf'])->name('generate.csr');
Route::post('/upload-csr', [CSRController::class, 'uploadCSR'])->name('upload.csr');
Route::post('/generate-csr-pdf', [PDFController::class, 'parsePdf'])->name('generate.csr.pdf');

Route::get('/upload-success', [CSRController::class, 'showSuccessPage'])->name('upload.success');
Route::get('/upload-failure', [CSRController::class, 'showFailurePage'])->name('upload.failure');
Route::post('/generate-final-csr', [CSRController::class, 'generateFinalCsr'])->name('generate.final.csr');
