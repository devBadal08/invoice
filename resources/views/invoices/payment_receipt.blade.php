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

.red-text { color: #9B0000; }
</style>
</head>

<body>

<table>

    <!-- HEADER -->
    <tr>
        <td colspan="4" class="center bold">
            <div class="heading">SIDDHI VINAYAK TRANSPORT</div>
            <div class="subheading">FORKLIFT & HYDRA CRANE</div>
            <div class="subheading2">PAYMENT RECEIPT</div>
        </td>
    </tr>

    <tr>
        <td colspan="4" class="center red-text bold">
            Regd Off+B1:K6ice : C1/20 Nandesari G.I.D.C. Colony, Nandesari, Vadodara  
            (M) 97233 96060, 91738 76050
        </td>
    </tr>

    <!-- CUSTOMER + RECEIPT INFO -->
    <tr>
        <td colspan="2">
            <b>Received From:</b><br>
            {{ $receipt->customer['name'] ?? '' }}<br>
            {{ $receipt->customer['address'] ?? '' }}
        </td>

        <td class="bold red-text">Receipt No</td>
        <td>{{ $receipt->receipt_no }}</td>
    </tr>

    <tr>
        <td colspan="2" class="bold red-text">
            GST No: {{ $receipt->customer['gst_no'] ?? '' }}
        </td>

        <td class="bold red-text">Date</td>
        <td>{{ \Carbon\Carbon::parse($receipt->date)->format('d-m-Y') }}</td>
    </tr>

    <!-- PAYMENT TABLE -->
    <tr class="bold center">
        <td>Description</td>
        <td>Payment Mode</td>
        <td colspan="2">Amount</td>
    </tr>

    <tr>
        <td>
            Payment received against Invoice  
            <b>{{ $receipt->invoice_no ?? '' }}</b>
        </td>
        <td class="center">
            {{ ucfirst($receipt->payment_method ?? 'Cash') }}
        </td>
        <td colspan="2" class="right">
            {{ number_format($receipt->amount, 2) }}
        </td>
    </tr>

    <!-- TOTAL -->
    <tr>
        <td colspan="2" class="bold red-text">
            Amount in Words:
            {{ strtoupper(\NumberFormatter::create('en_IN', \NumberFormatter::SPELLOUT)->format($receipt->amount)) }}
            ONLY
        </td>
        <td class="right bold red-text">TOTAL</td>
        <td class="right bold">{{ number_format($receipt->amount, 2) }}</td>
    </tr>

    <!-- BANK + SIGN -->
    <tr>
        <td colspan="2" class="red-text">
            <b>Bank:</b> Bank of Baroda<br>
            <b>Branch:</b> Nandesari<br>
            <b>Ac No:</b> 2170200001129<br>
            <b>IFSC:</b> BARBOINDNAN
        </td>

        <td colspan="2" class="center red-text">
            For <b>Siddhi Vinayak Transport</b><br><br><br>
            Authorised Signatory
        </td>
    </tr>

</table>

</body>
</html>
