<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaInvoices extends Model
{
    protected $fillable = [
        'company_id',
        'proforma_invoice_no',
        'invoice_date',
        'customer',
        'seller',
        'bank_details',
        'gst_type',
        'gst_rate',
        'items',
        'subtotal',
        'advancePayment',
        'amount',
        'terms',
        'declaration',
        'signatureName',
    ];

    protected $casts = [
        'customer'      => 'array',
        'seller'        => 'array',
        'bank_details'  => 'array',
        'gst_rate'       => 'array',
        'items'          => 'array',
        'amount'         => 'float',
        'advancePayment' => 'float',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected static function booted()
    {
        static::creating(function ($invoice) {

            /* ========== PROFORMA NUMBER ========== */
            if (empty($invoice->proforma_invoice_no)) {
                $invoice->proforma_invoice_no = self::generateNextProformaNumber();
            }

            /* ========== SELLER AUTO ========== */
            $invoice->seller = [
                'name'    => 'TECHSTROTA',
                'address' => '156, 1st Floor, C Tower, K10 Atlantis, Sarabhai Campus, Vadodara - 390007',
                'phone'   => '+91-81288 40055',
                'email'   => 'info@techstrota.com',
            ];

        });
    }

    public static function generateNextProformaNumber(): string
    {
        $monthYear = date('m-Y');

        $numbers = self::whereNotNull('proforma_invoice_no')
            ->pluck('proforma_invoice_no')
            ->map(function ($item) {

                if (preg_match('/TS\/PI(\d+)\//', $item, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            });

        $lastNumber = $numbers->max() ?? 0;
        $next = $lastNumber + 1;

        return 'TS/PI' . str_pad($next, 2, '0', STR_PAD_LEFT) . '/' . $monthYear;
    }

    /* For showing in create page (Filament placeholder) */
    public static function previewNextProformaNumber(): string
    {
        return self::generateNextProformaNumber();
    }
}
