<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProformaInvoices;
use Barryvdh\DomPDF\Facade\Pdf;

class ProformaInvoiceController extends Controller
{
    public function download($id)
    {
        $invoice = ProformaInvoices::findOrFail($id);

        // Make number safe (remove / and \)
        $safeNumber = str_replace(['/', '\\'], '-', $invoice->proforma_invoice_no);

        // Add prefix
        $filename = 'proforma_' . $safeNumber . '.pdf';

        $pdf = Pdf::loadView('invoices.proforma-invoice', compact('invoice'))
                    ->setPaper('A4', 'portrait');

        return $pdf->download($filename);
    }
}
