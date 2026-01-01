<?php

use Illuminate\Support\Facades\Route;
use App\Models\Invoices;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProformaInvoiceController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/invoice/{invoice}/pdf', function (Invoices $invoice) {
    $pdf = Pdf::loadView('invoices.pdf', compact('invoice'))
        ->setPaper('A4', 'portrait');

    $cleanInvoiceNo = str_replace(['/', '\\'], '-', $invoice->invoice_no);

    return $pdf->download('Invoice_' . $cleanInvoiceNo . '.pdf');
})->name('invoice.pdf');

Route::get('/receipt/{receipt}', [InvoiceController::class, 'generateReceipt'])
    ->name('payment.receipt');

Route::get('/proforma/{id}/download', [ProformaInvoiceController::class, 'download'])
    ->name('proforma.download');
