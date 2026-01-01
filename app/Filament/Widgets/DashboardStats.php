<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Invoices;
use App\Models\ProformaInvoices;
use App\Models\PaymentReceipt;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Companies', Company::count())
                ->description('Registered businesses')
                ->icon('heroicon-o-building-office')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('Proforma Invoices', ProformaInvoices::count())
                ->description('Draft & quotations')
                ->icon('heroicon-o-document-text')
                ->color('warning'),

            Stat::make('Invoices', Invoices::count())
                ->description('Finalized invoices')
                ->icon('heroicon-o-document-check')
                ->color('success'),

            Stat::make('Payment Receipts', PaymentReceipt::count())
                ->description('Payments received')
                ->icon('heroicon-o-banknotes')
                ->color('info'),
        ];
    }
}

