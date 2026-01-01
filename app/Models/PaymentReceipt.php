<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Company;

class PaymentReceipt extends Model
{
    protected $fillable = [
        'company_id',
        'receipt_no',
        'customer',
        'gst_type',
        'gst_rate',
        'payments',
        'amount',
    ];

    protected $casts = [
        'customer' => 'array',
        'payments' => 'array',
        'gst_rate' => 'array',
        'amount'   => 'float',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected static function booted()
    {
        static::creating(function ($receipt) {

            /* ========== RECEIPT NUMBER ========== */
            if (empty($receipt->receipt_no)) {
                $receipt->receipt_no = self::generateNextReceiptNumber();
            }

        });
    }

    public static function generateNextReceiptNumber(): string
    {
        $monthYear = date('m-Y');

        // Collect all existing receipt numbers
        $numbers = self::whereNotNull('payments')->get()
            ->pluck('payments')
            ->flatten(1)
            ->pluck('receipt_no')
            ->filter()
            ->map(function ($item) {
                if (preg_match('/TS\/PR(\d+)\//', $item, $matches)) {
                    return (int) $matches[1];
                }
                return 0;
            });

        $lastNumber = $numbers->max() ?? 0;
        $next = $lastNumber + 1;

        return 'TS/PR' . str_pad($next, 2, '0', STR_PAD_LEFT) . '/' . $monthYear;
    }
}
