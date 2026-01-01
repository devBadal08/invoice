<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentReceiptResource\Pages;
use App\Filament\Resources\PaymentReceiptResource\RelationManagers;
use App\Models\PaymentReceipt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Company;

class PaymentReceiptResource extends Resource
{
    protected static ?string $model = PaymentReceipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Payment Receipts';
    protected static ?string $navigationGroup = 'Invoices Management';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                /* ========== Payment Receipt number ========== */
                Forms\Components\Hidden::make('receipt_no')
                    ->default(fn () => PaymentReceipt::generateNextReceiptNumber()),

                Forms\Components\Placeholder::make('display_receipt_no')
                    ->label('Receipt No')
                    ->content(fn ($record) =>
                        $record?->receipt_no ?? PaymentReceipt::generateNextReceiptNumber()
                    ),

                Forms\Components\Select::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, $set) {

                        $company = Company::find($state);

                        if (! $company) {
                            return;
                        }

                        // Autofill CUSTOMER details
                        $set('customer.name', $company->name);
                        $set('customer.gst_no', $company->gst_no);
                        $set('customer.address', $company->address);

                        // Auto-fill SELLER snapshot
                        $set('seller', [
                            'name'     => $company->name,
                            'gst_no'   => $company->gst_no,
                            'address'  => $company->address,
                        ]);
                    }),

                /* ========== CUSTOMER DETAILS (JSON) ========== */
                Forms\Components\Fieldset::make('Customer Details')
                    ->schema([
                        Forms\Components\TextInput::make('customer.name')->required()->disabled()->dehydrated(),
                        Forms\Components\TextInput::make('customer.gst_no')->label('GST No')->disabled()->dehydrated(),
                        Forms\Components\Textarea::make('customer.address')->required()->disabled()->rows(3)->dehydrated(),
                    ]),

                /* ========== GST TYPE ========== */
                Forms\Components\Select::make('gst_type')
                    ->options([
                        'cgst_sgst' => 'CGST + SGST',
                        'igst' => 'IGST',
                        'no_gst' => 'No GST',
                    ])
                    ->required()
                    ->reactive()
                    ->columnSpanFull()
                    ->afterStateUpdated(fn ($set, $get) => self::updateTotalFromInstallments($set, $get)),

                /* ===== GST RATE (JSON) ===== */
                Forms\Components\Group::make()
                    ->visible(fn ($get) => $get('gst_type') === 'cgst_sgst')
                    ->schema([
                        Forms\Components\TextInput::make('gst_rate.cgst')
                            ->label('CGST %')
                            ->numeric()
                            ->reactive()
                            ->afterStateUpdated(fn ($set, $get) => self::updateTotalFromInstallments($set, $get)),

                        Forms\Components\TextInput::make('gst_rate.sgst')
                            ->label('SGST %')
                            ->numeric()
                            ->reactive()
                            ->afterStateUpdated(fn ($set, $get) => self::updateTotalFromInstallments($set, $get)),
                    ]),

                Forms\Components\TextInput::make('gst_rate.igst')
                    ->label('IGST %')
                    ->numeric()
                    ->reactive()
                    ->visible(fn ($get) => $get('gst_type') === 'igst')
                    ->afterStateUpdated(fn ($set, $get) => self::updateTotalFromInstallments($set, $get)),

                /* ========== Payment ========== */
                Forms\Components\Repeater::make('payments')
                    ->label('Payments')
                    ->schema([
                        // Forms\Components\TextInput::make('receipt_no')
                        //     ->label('Receipt No')
                        //     ->default(fn () => PaymentReceipt::generateNextReceiptNumber())
                        //     ->disabled()
                        //     ->dehydrated()
                        //     ->unique()
                        //     ->required(),

                        Forms\Components\Select::make('method')
                            ->label('Payment Method')
                            ->options([
                                'Cash'        => 'Cash',
                                'UPI'         => 'UPI',
                                'Bank Transfer' => 'Bank Transfer',
                                'Cheque'      => 'Cheque',
                                'Card'         => 'Card',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required(),

                        Forms\Components\DatePicker::make('date')
                            ->required(),
                    ])
                    ->reactive()
                    ->columns(4)
                    ->afterStateUpdated(fn ($set, $get) => self::updateTotalFromInstallments($set, $get))
                    ->columnSpanFull(),

                /* ========== TOTAL AMOUNT ========== */
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->columnSpanFull()
                    ->reactive()
                    ->required(),
            ]);
    }

    public static function updateTotalFromInstallments($set, $get): void
    {
        $payments = $get('payments') ?? [];

        // 1. Total of all payments
        $subtotal = collect($payments)->sum(function ($payment) {
            return floatval($payment['amount'] ?? 0);
        });

        // 2. GST
        $gstType = $get('gst_type');

        if ($gstType === 'no_gst') {
            $total = $subtotal;
        } 
        elseif ($gstType === 'cgst_sgst') {
            $cgstRate = floatval($get('gst_rate.cgst') ?: 0);
            $sgstRate = floatval($get('gst_rate.sgst') ?: 0);

            $cgst = ($subtotal * $cgstRate) / 100;
            $sgst = ($subtotal * $sgstRate) / 100;

            $total = $subtotal + $cgst + $sgst;
        } 
        else { // IGST
            $igstRate = floatval($get('gst_rate.igst') ?: 0);

            $igst = ($subtotal * $igstRate) / 100;

            $total = $subtotal + $igst;
        }

        $set('amount', round($total, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('receipt_no')->label('Receipt No'),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer'),
                Tables\Columns\TextColumn::make('amount')->money('INR'),    
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Payment Receipt')
                    ->icon('heroicon-o-receipt-refund')
                    ->color('success')
                    ->url(fn (PaymentReceipt $record) => route('payment.receipt', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('id','desc')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentReceipts::route('/'),
            'create' => Pages\CreatePaymentReceipt::route('/create'),
            'edit' => Pages\EditPaymentReceipt::route('/{record}/edit'),
        ];
    }

    // public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    // {
    //     return parent::getEloquentQuery()
    //         ->whereNotNull('payments')
    //         ->where('payments', '!=', '[]');
    // }
}
