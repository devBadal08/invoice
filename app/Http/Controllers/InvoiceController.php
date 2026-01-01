<?php

namespace App\Http\Controllers;

use App\Models\PaymentReceipt;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function generateReceipt(PaymentReceipt $receipt)
    {
        $payments = collect($receipt->payments ?? []);

        if ($payments->isEmpty()) {
            abort(404, 'No payment found');
        }

        $latestIndex = $payments->count() - 1;
        $payment = $payments[$latestIndex];

        if (!isset($payment['receipt_no'])) {

            $payment['receipt_no'] = $receipt->receipt_no 
                ?? PaymentReceipt::generateNextReceiptNumber();

            $payments[$latestIndex] = $payment;

            $receipt->payments = $payments->values()->all();
            $receipt->receipt_no = $payment['receipt_no'];
            $receipt->save();
        }

        $cleanReceipt = str_replace(['/', '\\'], '-', $payment['receipt_no']);

        return Pdf::loadView('invoices.payment_receipt', [
            'receipt' => $receipt,
            'payment' => $payment,
        ])
        ->setPaper('A4', 'portrait')
        ->download('Receipt-' . $cleanReceipt . '.pdf');
    }
}
