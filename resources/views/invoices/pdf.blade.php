@php
    // 1. Keep only items that actually have a description
    $items = collect($invoice->items)
        ->filter(fn ($item) => !empty($item['description']))
        ->values();

    // 2. Calculate subtotal ONLY from valid items
    $subtotal = $items->sum(fn ($item) => ($item['quantity'] ?? 0) * ($item['rate'] ?? 0));

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

.sub {
    font-size: 12px;
}

.red-text {
    color: #9B0000;
}

.no-horizontal td {
    border-top: none;
    border-bottom: none;
}

</style>
</head>

<body>

<table>

    <!-- TOP SPACE -->
    <tr><td colspan="6" style="height:20px;border:none"></td></tr>

    <!-- HEADER -->
    <tr>
        <td colspan="6" class="center bold">
            <div class="heading">SIDDHI VINAYAK TRANSPORT</div>
            <div class="subheading">FORKLIFT & HYDRA CRANE</div> 
        </td>
    </tr>
    <tr>
        <td colspan="6" class="center bold">
            <div class="subheading2">ALL TYPE LOADING - UNLOADING, SHIFTING 24 HOURS</div>
        </td>
    </tr>

    <!-- ADDRESS -->
    <tr>
        <td colspan="6" class="center sub red-text bold">
            Regd Off+B1:K6ice : C1/20 Nandesari G.I.D.C. Colony, Nandesari, Vadodara (M) 97233 96060, 91738 76050
        </td>
    </tr>

    <!-- PARTY & BILL -->
    <tr>
        <td colspan="4">
            {{ $invoice->customer['name'] }}
        </td>
        <td class="bold red-text">Invoice No</td>
        <td>{{ $invoice->invoice_no }}</td>
    </tr>

    <tr>
        <td colspan="4">{{ $invoice->customer['address'] }}</td>
        <td class="bold red-text">Date</td>
        <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d-m-Y') }}</td>
    </tr>

    <tr>
        <td colspan="6" class="bold red-text"><b>GST No:</b> {{ $invoice->customer['gst_no'] ?? '' }}</td>
    </tr>

    <tr>
        <td colspan="3" class="bold red-text" width="15%">Job Site : {{ $invoice->job_site }}</td>
        <td colspan="3" class="bold center red-text">Subject to Your Terms & Condition</td>
    </tr>

    <!-- TABLE HEADER -->
    <tr class="bold center">
        <td width="10%" class="red-text">Date</td>
        <td width="30%" class="red-text">Description</td>
        <td width="10%" class="red-text">HSN Code</td>
        <td width="10%" class="red-text">Total Hours/ Days</td>
        <td width="15%" class="red-text">Rate</td>
        <td width="15%" class="red-text">Amount</td>
    </tr>

    <!-- ITEMS -->
    @foreach($items as $item)
        @php $line = ($item['quantity'] ?? 0) * ($item['rate'] ?? 0); @endphp
        <tr>
            <td class="center">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d-m-Y') }}</td>
            <td>{{ $item['description'] }}</td>
            <td class="center">{{ $invoice->bank_details['hsncode'] ?? '' }}</td>
            <td class="center">
                {{ $item['quantity'] }} {{ ucfirst($item['unit'] ?? '') }}
            </td>
            <td class="right">{{ number_format($item['rate'],2) }}</td>
            <td class="right">{{ number_format($line,2) }}</td>
        </tr>
    @endforeach

    @php
        $maxRows = 6;
        $currentRows = $items->count();
        $emptyRows = max(0, $maxRows - $currentRows);
    @endphp

    @for($i = 0; $i < $emptyRows; $i++)
    <tr class="no-horizontal">
        <td style="height:50px"></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    @endfor

    <!-- TOTALS -->
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td class="right bold red-text">TOTAL</td>
        <td class="right bold">{{ number_format($subtotal,2) }}</td>
    </tr>

    {{-- CGST + SGST --}}
    @if($invoice->gst_type === 'cgst_sgst')
    <tr>
        <td></td><td></td><td></td><td></td>
        <td class="right red-text">
            CGST {{ $invoice->gst_rate['cgst'] ?? 0 }}%
        </td>
        <td class="right">{{ number_format($cgst, 2) }}</td>
    </tr>

    <tr>
        <td></td><td></td><td></td><td></td>
        <td class="right red-text">
            SGST {{ $invoice->gst_rate['sgst'] ?? 0 }}%
        </td>
        <td class="right">{{ number_format($sgst, 2) }}</td>
    </tr>
    @endif

    {{-- IGST --}}
    @if($invoice->gst_type === 'igst')
    <tr>
        <td></td><td></td><td></td><td></td>
        <td class="right red-text">
            IGST {{ $invoice->gst_rate['igst'] ?? 0 }}%
        </td>
        <td class="right">{{ number_format($igst, 2) }}</td>
    </tr>
    @endif

    {{-- NO GST--}}
    @if($invoice->gst_type === 'no_gst')
    <tr>
        <td></td><td></td><td></td><td></td>
        <td class="right red-text">
            CGST {{ $invoice->gst_rate['cgst'] ?? 0 }}%
        </td>
        <td class="right">{{ number_format($cgst, 2) }}</td>
    </tr>

    <tr>
        <td></td><td></td><td></td><td></td>
        <td class="right red-text">
            SGST {{ $invoice->gst_rate['sgst'] ?? 0 }}%
        </td>
        <td class="right">{{ number_format($sgst, 2) }}</td>
    </tr>
    @endif

    <tr class="bold">
        <td colspan="4">
            <b><div class="red-text">Amount in Words:</div></b>
            {{ strtoupper(\NumberFormatter::create('en_IN', \NumberFormatter::SPELLOUT)->format($grandTotal)) }} ONLY
        </td>
        <td class="right red-text">G. TOTAL</td>
        <td class="right">{{ number_format($grandTotal,2) }}</td>
    </tr>

    <tr>
        <td colspan="4" class="red-text"><b>GST NO:</b> 24BCQPP5618E1ZF</td>
        <td colspan="2" class="center red-text">For <b>Siddhi Vinayak Transport</b></td>
    </tr>

    <tr>
        <td colspan="4" class="red-text"><b>Bank:</b> Bank of Baroda</td>
        <td colspan="2" class="red-text" rowspan="3"></td>
    </tr>

    <tr>
        <td colspan="4" class="red-text"><b>Branch:</b> Nandesari</td>
    </tr>

    <tr>
        <td colspan="4" class="red-text"><b>Ac No:</b> 2170200001129</td>
    </tr>

    <tr>
        <td colspan="4" class="red-text"><b>IFSCCode:</b> BARBOINDNAN</td>
        <td colspan="2" class="center red-text">Authorised Signatory</td>
    </tr>

</table>

</body>
</html>
