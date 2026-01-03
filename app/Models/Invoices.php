<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Company;

class Invoices extends Model
{
    protected $fillable = [
        'company_id',
        'invoice_no',
        'invoice_date',
        'job_site',
        'customer_name',
        'amount',
        'gst_type',
        'gst_rate',
        'items',
        'installments',
        'pdf_path',
        'seller',
        'customer',
        'bank_details',
        'terms',
        'declaration',
        'signatureName',
        'advancePayment',
    ];

    protected $casts = [
        'items' => 'array',
        'installments' => 'array',
        'gst_rate' => 'array',
        'seller' => 'array',
        'customer' => 'array',
        'bank_details' => 'array',
        'invoice_date' => 'date',
        'advancePayment' => 'float',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected static function booted()
    {
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_no)) {
                $invoice->invoice_no = self::generateNextInvoiceNumber();
            }

            /* ========== SELLER AUTO ========== */
            $invoice->seller = [
                'name'    => 'TECHSTROTA',
                'address' => '156, 1st Floor, C Tower, K10 Atlantis, Sarabhai Campus, Vadodara - 390007',
                'phone'   => '+91-81288 40055',
                'email'   => 'info@techstrota.com',
            ];

            $invoice->invoice_date = now();

            // If NO GST selected, save empty gst_rate
            if ($invoice->gst_type === 'no_gst') {
                $invoice->gst_rate = json_encode([]);
            }

            /* ========== CALCULATE TOTAL (AMOUNT) ========== */

            $items = $invoice->items ?? [];

            $subtotal = collect($items)->sum(function ($item) {
                return ($item['quantity'] ?? 0) * ($item['rate'] ?? 0);
            });

            $advance = $invoice->advancePayment ?? 0;

            if ($invoice->gst_type === 'no_gst') {
                $total = $subtotal - $advance;
            }

            elseif ($invoice->gst_type === 'cgst_sgst') {
                $cgstRate = $invoice->gst_rate['cgst'] ?? 0;
                $sgstRate = $invoice->gst_rate['sgst'] ?? 0;

                $cgst = ($subtotal * $cgstRate) / 100;
                $sgst = ($subtotal * $sgstRate) / 100;

                $total = $subtotal + $cgst + $sgst - $advance;
            }

            else { // igst
                $igstRate = $invoice->gst_rate['igst'] ?? 0;
                $igst = ($subtotal * $igstRate) / 100;

                $total = $subtotal + $igst - $advance;
            }

            $invoice->amount = round($total, 2);
        });
    }

    public static function generateNextInvoiceNumber(): string
    {
        $month = date('m');   // 11
        $year  = date('Y');   // 2025
        $monthYear = $month . '-' . $year; // 11-2025

        // Get last invoice from same month & year
        $lastInvoice = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice) {
            // Example: TS/INV05/11-2025
            preg_match('/INV(\d+)/', $lastInvoice->invoice_no, $matches);
            $lastNumber = isset($matches[1]) ? (int) $matches[1] : 0;
        } else {
            $lastNumber = 0;
        }

        $next = $lastNumber + 1;

        return 'TS/INV' . str_pad($next, 2, '0', STR_PAD_LEFT) . '/' . $monthYear;
    }
}
