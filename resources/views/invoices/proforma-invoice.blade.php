@php
    // FILTER VALID ITEMS
    $items = collect($invoice->items ?? [])
        ->filter(fn ($item) => !empty($item['description']))
        ->values();

    // SUBTOTAL
    $subtotal = $items->sum(fn ($item) =>
        ($item['qty'] ?? 0) * ($item['rate'] ?? 0)
    );

    // GST
    $cgst = $sgst = $igst = 0;

    if ($invoice->gst_type === 'cgst_sgst') {
        $cgst = $subtotal * (($invoice->gst_rate['cgst'] ?? 0) / 100);
        $sgst = $subtotal * (($invoice->gst_rate['sgst'] ?? 0) / 100);
    }

    if ($invoice->gst_type === 'igst') {
        $igst = $subtotal * (($invoice->gst_rate['igst'] ?? 0) / 100);
    }

    $grandTotal = $subtotal + $cgst + $sgst + $igst;
@endphp

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Proforma Invoice</title>

<style>
@page { margin: 20px; }

body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 12px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

td, th {
    border: 1px solid #9B0000;
    padding: 6px;
    vertical-align: top;
}

.center { text-align: center; }
.right { text-align: right; }
.bold { font-weight: bold; }

.heading {
    font-size: 25px;
    font-weight: bold;
    color: #9B0000;
}

.subheading {
    font-size: 20px;
    font-weight: bold;
    color: #9B0000;
}

.subheading2 {
    font-size: 15px;
    color: #9B0000;
}

.red-text { color: #9B0000; }

.no-horizontal td {
    border-top: none;
    border-bottom: none;
}
</style>
</head>

<body>

<table>

    <!-- HEADER -->
    <tr>
        <td colspan="6" class="center bold">
            <div class="heading">SIDDHI VINAYAK TRANSPORT</div>
            <div class="subheading">FORKLIFT & HYDRA CRANE</div>
            <div class="subheading2">PROFORMA INVOICE</div>
        </td>
    </tr>

    <tr>
        <td colspan="6" class="center red-text bold">
            ALL TYPE LOADING - UNLOADING, SHIFTING 24 HOURS
        </td>
    </tr>

    <tr>
        <td colspan="6" class="center red-text">
            Regd Off+B1:K6ice : C1/20 Nandesari G.I.D.C. Colony, Nandesari, Vadodara (M)
            97233 96060, 91738 76050
        </td>
    </tr>

    <!-- PARTY DETAILS -->
    <tr>
        <td colspan="4">{{ $invoice->customer['name'] ?? '' }}</td>
        <td class="bold red-text">Proforma No</td>
        <td>{{ $invoice->proforma_invoice_no ?? '-' }}</td>
    </tr>

    <tr>
        <td colspan="4">{{ $invoice->customer['address'] ?? '' }}</td>
        <td class="bold red-text">Date</td>
        <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d-m-Y') }}</td>
    </tr>

    <tr>
        <td colspan="6" class="bold red-text">
            <b>GST No:</b> {{ $invoice->customer['gst_no'] ?? '' }}
        </td>
    </tr>

    <tr>
        <td colspan="3" class="bold red-text">
            Job Site : {{ $invoice->job_site ?? '-' }}
        </td>
        <td colspan="3" class="bold center red-text">
            Subject to Your Terms & Condition
        </td>
    </tr>

    <!-- TABLE HEADER -->
    <tr class="bold center">
        <td>Date</td>
        <td>Description</td>
        <td>HSN Code</td>
        <td>Total Hours / Days</td>
        <td>Rate</td>
        <td>Amount</td>
    </tr>

    <!-- ITEMS -->
    @foreach($items as $item)
        @php
            $line = ($item['qty'] ?? 0) * ($item['rate'] ?? 0);
        @endphp
        <tr>
            <td class="center">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d-m-Y') }}</td>
            <td>{{ $item['description'] }}</td>
            <td class="center">{{ $invoice->bank_details['hsncode'] ?? '' }}</td>
            <td class="center">
                {{ $item['qty'] }}
            </td>
            <td class="right">{{ number_format($item['rate'],2) }}</td>
            <td class="right">{{ number_format($line,2) }}</td>
        </tr>
    @endforeach

    <!-- EMPTY ROWS -->
    @for($i = 0; $i < max(0, 6 - $items->count()); $i++)
    <tr class="no-horizontal">
        <td style="height:40px"></td>
        <td></td><td></td><td></td><td></td><td></td>
    </tr>
    @endfor

    <!-- TOTALS -->
    <tr>
        <td colspan="4"></td>
        <td class="right bold red-text">TOTAL</td>
        <td class="right bold">{{ number_format($subtotal,2) }}</td>
    </tr>

    @if($invoice->gst_type === 'cgst_sgst')
    <tr>
        <td colspan="4"></td>
        <td class="right red-text">CGST {{ $invoice->gst_rate['cgst'] }}%</td>
        <td class="right">{{ number_format($cgst,2) }}</td>
    </tr>
    <tr>
        <td colspan="4"></td>
        <td class="right red-text">SGST {{ $invoice->gst_rate['sgst'] }}%</td>
        <td class="right">{{ number_format($sgst,2) }}</td>
    </tr>
    @endif

    @if($invoice->gst_type === 'igst')
    <tr>
        <td colspan="4"></td>
        <td class="right red-text">IGST {{ $invoice->gst_rate['igst'] }}%</td>
        <td class="right">{{ number_format($igst,2) }}</td>
    </tr>
    @endif

    <tr class="bold">
        <td colspan="4">
            <b class="red-text">Amount in Words:</b><br>
            {{ strtoupper(\NumberFormatter::create('en_IN', \NumberFormatter::SPELLOUT)->format($grandTotal)) }} ONLY
        </td>
        <td class="right red-text">G. TOTAL</td>
        <td class="right">{{ number_format($grandTotal,2) }}</td>
    </tr>

    <!-- BANK + SIGN -->
    <tr>
        <td colspan="4" class="red-text"><b>Bank:</b> {{ $invoice->bank_details['branch'] ?? '' }}</td>
        <td colspan="2" class="center red-text">For <b>Siddhi Vinayak Transport</b></td>
    </tr>

    <tr>
        <td colspan="4" class="red-text"><b>Ac No:</b> {{ $invoice->bank_details['account'] ?? '' }}</td>
        <td colspan="2" rowspan="2"></td>
    </tr>

    <tr>
        <td colspan="4" class="red-text"><b>IFSC:</b> {{ $invoice->bank_details['ifsc'] ?? '' }}</td>
    </tr>

    <tr>
        <td colspan="4"></td>
        <td colspan="2" class="center red-text">Authorised Signatory</td>
    </tr>

</table>

</body>
</html>
