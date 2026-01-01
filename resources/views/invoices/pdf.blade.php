<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_no }}</title>

    <style>
        @page {
            margin-top: 10px;
            margin-left: 10px;
            margin-right: 10px;
            margin-bottom: 20px; /* IMPORTANT – space for footer */
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 14px;
            margin: 0;
            padding: 0;
            color: #222;
        }

        .wrap {
            padding: 5px 12px 10px 12px;
        }

        

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #f59e0b;
        }

        .divider {
            border-top: 2px solid #e5f0ff;
            margin: 5px 0 5px 0;
        }

        /* INFO BOXES */
        .info-boxes {
            display: table;
            width: 100%;
            border-spacing: 12px 0;
            margin: 12px 0 10px 0;
        }

        .box {
            display: table-cell;
            background: #f4f9ff;
            border-radius: 20px;
            padding: 12px 16px;
            font-size: 13px;
            border: 1px solid #e2edff;
        }

        strong {
            font-size: 15px;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid #e6efff;
        }

        th {
            background: #f2f7ff;
            padding: 10px 12px;
            color: #0f3f88;
            font-size: 14px;
            border-bottom: 1px solid #e2edff;
        }

        td {
            padding: 10px 12px;
            font-size: 13px;
            border-bottom: 1px solid #f0f5ff;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .right {
            text-align: right;
        }

        .totals td {
            font-weight: bold;
        }

        .grand td {
            background: #fff5ea;
            color: #cc6a00;
            font-size: 16px;
        }

        /* PAYMENT + BANK */
        .bottom-boxes {
            display: table;
            width: 100%;
            margin-top: 25px;
        }

        .bottom-box {
            background: #f7fbff;
            border-radius: 20px;
            padding: 16px;
            border: 1px solid #e2eeff;
            font-size: 13px;
        }

        /* WORDS */
        .words {
            margin-top: 20px;
            margin-bottom: 10px;
            font-style: italic;
        }

        /* TERMS */
        .terms {
            margin-top: 20px;
            font-size: 13px;
        }

        /* SIGNATURE */
        .sign {
            margin-top: 30px;
            width: 240px;
            padding: 12px;
            border: 2px dashed #ff9f00;
            border-radius: 16px;
            color: #1a4fd8;
            text-align: center;
            margin-left: auto;
            font-size: 13px;
            background: #fffaf3;
        }

        /* FOOTER */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #f59e0b;
            color: #000;
            padding: 10px;
            text-align: center;
            font-weight: bold;
        }

        .header-card {
            background: #ffffff;
            padding: 10px 14px;
            border-radius: 20px;
            position: relative;
            border: none;
        }

        .header-card::after {
            content: "";
            position: absolute;
            left: 25%;
            right: 25%;
            bottom: -3px;
            height: 3px;
            background: linear-gradient(90deg, #ff9f00, #ff7b00);
            border-radius: 20px;
        }

        .invoice-title {
            font-size: 30px;
            font-weight: 900;
            color: #ff8c00;
            letter-spacing: 3px;
            margin: 0;
        }

        .company-address {
            font-size: 11px;
            line-height: 1.3;
        }
    </style>
</head>

<body>
<div class="wrap">

    <!-- HEADER CARD -->
    <div class="header-card">
        <table width="100%">
            <tr>
                <!-- LEFT LOGO -->
                <td width="30%" valign="middle">
                    <img src="{{ public_path('images/logo.png') }}" height="40">
                </td>

                <!-- CENTER TITLE -->
                <td width="40%" align="center" valign="middle">
                    <div class="invoice-title">INVOICE</div>
                </td>

                <!-- RIGHT COMPANY INFO -->
                <td width="30%" align="right" valign="middle">
                    <div class="company-name">TECHSTROTA</div>
                    <div class="company-address">
                        {{ $invoice->seller['address'] ?? '' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- INFO BOXES -->
    <div class="info-boxes">

        <div class="box">
            <strong>To:</strong> {{ $invoice->customer['name'] ?? '-' }}<br>
            <strong>Address:</strong> {{ $invoice->customer['address'] ?? '-' }}
        </div>

        <div class="box box-right">
            <strong>Invoice No:</strong> {{ $invoice->invoice_no }}<br>
            <strong>Date:</strong> {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') }}
        </div>

    </div>

    <!-- ITEMS TABLE -->
    <table>
        <thead>
        <tr>
            <th>Description</th>
            <th class="right">Qty</th>
            <th class="right">HSN Code</th>
            <th class="right">Rate/Unit</th>
            <th class="right">Amount</th>
        </tr>
        </thead>

        <tbody>
        @php $subtotal = 0; @endphp

        @foreach($invoice->items as $item)
            @php
                $line = ($item['qty'] ?? 0) * ($item['rate'] ?? 0);
                $subtotal += $line;
            @endphp
            <tr>
                <td>{{ $item['description'] }}</td>
                <td class="right">{{ $item['qty'] }}</td>
                <td class="right">{{ $invoice->bank_details['hsncode'] ?? '' }}</td>
                <td class="right">₹ {{ number_format($item['rate'], 2) }}</td>
                <td class="right">₹ {{ number_format($line, 2) }}</td>
            </tr>
        @endforeach
        
        @php
            $cgst = 0;
            $sgst = 0;
            $igst = 0;

            if ($invoice->gst_type === 'cgst_sgst') {
                $cgst = $subtotal * ($invoice->gst_rate['cgst'] ?? 0) / 100;
                $sgst = $subtotal * ($invoice->gst_rate['sgst'] ?? 0) / 100;
            }

            if ($invoice->gst_type === 'igst') {
                $igst = $subtotal * ($invoice->gst_rate['igst'] ?? 0) / 100;
            }

            $grandTotal = $subtotal + $cgst + $sgst + $igst;
            $advance = $invoice->advancePayment ?? 0;
            $balance = $grandTotal - $advance;
        @endphp
        </tbody>
        <br>

        <!-- TOTALS -->
        <tbody class="totals">
            <tr>
                <td colspan="4" class="right">Subtotal</td>
                <td class="right">₹ {{ number_format($subtotal, 2) }}</td>
            </tr>

            @if($invoice->gst_type === 'cgst_sgst')
                <tr>
                    <td colspan="4" class="right">
                        CGST @ {{ $invoice->gst_rate['cgst'] ?? 0 }} %
                    </td>
                    <td class="right">
                        ₹ {{ number_format($subtotal * ($invoice->gst_rate['cgst'] ?? 0) / 100, 2) }}
                    </td>
                </tr>

                <tr>
                    <td colspan="4" class="right">
                        SGST @ {{ $invoice->gst_rate['sgst'] ?? 0 }} %
                    </td>
                    <td class="right">
                        ₹ {{ number_format($subtotal * ($invoice->gst_rate['sgst'] ?? 0) / 100, 2) }}
                    </td>
                </tr>
            @endif

            @if($invoice->gst_type === 'igst')
                <tr>
                    <td colspan="4" class="right">
                        IGST @ {{ $invoice->gst_rate['igst'] ?? 0 }} %
                    </td>
                    <td class="right">
                        ₹ {{ number_format($subtotal * ($invoice->gst_rate['igst'] ?? 0) / 100, 2) }}
                    </td>
                </tr>
            @endif

        </tbody>

        <tbody class="grand">
            <tr>
                <td colspan="4" class="right"><strong>Grand Total</strong></td>
                <td class="right"><strong>₹ {{ number_format($grandTotal, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- PAYMENT / BANK -->
    <div class="bottom-boxes">
        <div class="bottom-box" style="width:100%">
            <table width="100%">
                <tr>
                    <!-- PAYMENT -->
                    <td width="50%" valign="top">
                        <strong style="color:#1d4ed8; font-size:15px;">Payment Details</strong><br><br>

                        <b>Invoice Amount:</b> ₹ {{ number_format($grandTotal,2) }} <br>
                        <b>Advance Receive:</b> ₹ {{ number_format($advance ?? 0,2) }} <br>
                        <b>Balance Payable:</b>
                        <span style="color:red">
                            ₹ {{ number_format($balance,2) }}
                        </span>
                    </td>

                    <!-- BANK -->
                    <td width="50%" valign="top">
                        <strong style="color:#1d4ed8; font-size:15px;">Bank Details</strong><br><br>

                        <b>Account No:</b> {{ $invoice->bank_details['account'] ?? '' }} <br>
                        <b>IFSC:</b> {{ $invoice->bank_details['ifsc'] ?? '' }} <br>
                        <b>Branch:</b> {{ $invoice->bank_details['branch'] ?? '' }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- WORDS -->
    <div class="words">
        <strong>Amount in Words:</strong>
        {{ strtoupper(\NumberFormatter::create('en_IN', \NumberFormatter::SPELLOUT)->format($invoice->amount)) }} RUPEES ONLY
    </div>

    <div class="divider"></div>

    <!-- TERMS -->
    <div class="terms">
        <strong>Terms:</strong> {{ $invoice->terms }}<br><br>
        <strong>Declaration:</strong><br>
        {{ $invoice->declaration }}
    </div>

    <!-- SIGNATURE -->
    <div class="sign">
        For,<strong>TECHSTROTA</strong><br><br><br>
        {{ $invoice->signatureName ?? 'Authorized Signature' }}
    </div>

</div>

<!-- FOOTER BAR -->
<div class="footer">
    Email: info@techstrota.com | Call Us: +91 90334 76660 | techstrota.com
</div>

</body>
</html>
