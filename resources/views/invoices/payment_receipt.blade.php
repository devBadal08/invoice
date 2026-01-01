<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $receipt->receipt_no ?? '' }}</title>

    <style>
        @page { margin: 10px 10px 20px 10px; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 14px;
        }

        .wrap { padding: 5px 12px; }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #f59e0b;
        }

        table {
            width:100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
            border-radius: 18px;
            border: 1px solid #e6efff;
        }

        th { background: #f2f7ff; padding: 10px; }
        td { padding: 10px; }

        .center { text-align:center; }
        .right { text-align:right; }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #f59e0b;
            padding: 10px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="wrap">

    <table width="100%">
        <tr>
            <td width="30%">
                <img src="{{ public_path('images/logo.png') }}" height="40">
            </td>

            <td width="40%" class="center">
                <h2 style="color:#ff8c00">PAYMENT RECEIPT</h2>
            </td>

            <td width="30%" class="right">
                <div class="company-name">TECHSTROTA</div>
                156, 1st Floor, C Tower, K10 Atlantis, Sarabhai Campus, Vadodara - 390007
            </td>
        </tr>
    </table>

    <br>

    <table>
        <tr>
            <td>
                <strong>Customer:</strong> {{ $receipt->customer['name'] ?? '' }} <br>
                <strong>Address:</strong> {{ $receipt->customer['address'] ?? '' }}
            </td>

            <td class="right">
                <strong>Receipt No:</strong> {{ $payment['receipt_no'] ?? $receipt->receipt_no ?? 'N/A' }} <br>
                <strong>Date:</strong> {{ isset($payment['date']) ? \Carbon\Carbon::parse($payment['date'])->format('d-m-Y') : now()->format('d-m-Y') }}
            </td>
        </tr>
    </table>

    <br>

    <table>
        <thead>
        <tr>
            <th>Description</th>
            <th class="right">Amount</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Payment Received ({{ $payment['method'] ?? 'N/A' }})</td>
            <td class="right">₹ {{ number_format($payment['amount'] ?? 0, 2) }}</td>
        </tr>
        </tbody>
    </table>

    <br>

    <br><br>
    <br><br>
    <br><br>
    <br><br>
    <br><br>
    <br><br>
    <br><br>
    <br><br>
    <br><br>
    <br><br>
    <!-- SIGNATURE BOX (BELOW + RIGHT SIDE) -->
    <table style="width:100%; border:none;">
        <tr>
            <td style="border:none; padding:0;">
                <div style="
                    width: 40%;
                    margin-left: auto;
                    border: 2px dashed #ff9f00;
                    border-radius: 16px;
                    padding: 25px 10px;
                    background: #fffaf3;
                    text-align: center;
                    min-height: 110px;
                ">
                    For,
                    <strong style="color:#1d4ed8; font-size:18px;">TECHSTROTA</strong>
                    <br><br><br>
                    Authorized Signature
                </div>
            </td>
        </tr>
    </table>

</div>

<div class="footer">
    Email: info@techstrota.com | Call Us: +91 90334 76660 | techstrota.com
</div>

</body>
</html>
