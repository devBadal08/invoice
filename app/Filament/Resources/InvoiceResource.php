<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoices;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Company;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoices::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Invoices';
    protected static ?string $navigationGroup = 'Invoices Management';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                /* ========== INVOICE NUMBER ========== */
                Forms\Components\Hidden::make('invoice_no'),

                Forms\Components\Placeholder::make('display_invoice_no')
                    ->label('Invoice No')
                    ->content(fn ($record) =>
                        $record?->invoice_no ?? Invoices::generateNextInvoiceNumber()
                    ),

                Forms\Components\DatePicker::make('invoice_date')
                    ->label('Invoice Date')
                    ->required()
                    ->default(now()),

                Forms\Components\TextInput::make('job_site')
                    ->label('Job Site')
                    ->placeholder('Enter job site / work location')
                    ->columnSpanFull(),

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

                /* ========== BANK DETAILS (JSON) ========== */
                Forms\Components\Fieldset::make('Bank Details')
                    ->schema([
                        Forms\Components\TextInput::make('bank_details.account')
                                ->label('Account No')
                                ->default('1147535073')
                                ->disabled()
                                ->dehydrated(true)
                                ->required(),
                            Forms\Components\TextInput::make('bank_details.ifsc')
                                ->label('IFSC')
                                ->default('KKBK0000841')
                                ->disabled()
                                ->dehydrated(true)
                                ->required(),
                            Forms\Components\TextInput::make('bank_details.branch')
                                ->label('Branch')
                                ->default('Vadodara - Race Course Circle')
                                ->disabled()
                                ->dehydrated(true)
                                ->required(),
                            Forms\Components\TextInput::make('bank_details.hsncode')
                                ->label('HSN Code')
                                ->default('997319')
                                ->disabled()
                                ->dehydrated(true)
                                ->required(),
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
                    ->columnSpanFull(),

                /* ===== GST RATE (JSON) ===== */
                Forms\Components\Group::make()
                    ->visible(fn ($get) => $get('gst_type') === 'cgst_sgst')
                    ->schema([
                        Forms\Components\TextInput::make('gst_rate.cgst')->label('CGST %')->numeric(),
                        Forms\Components\TextInput::make('gst_rate.sgst')->label('SGST %')->numeric(),
                    ]),

                Forms\Components\TextInput::make('gst_rate.igst')
                    ->label('IGST %')
                    ->numeric()
                    ->visible(fn ($get) => $get('gst_type') === 'igst'),

                /* ========== ITEMS (JSON REPEATER) ========== */
                Forms\Components\Repeater::make('items')
                    ->label('Invoice Items')
                    ->required()
                    ->schema([
                        Forms\Components\TextInput::make('description')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Total')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($set, $get) =>
                                        self::calculateSubTotal($set, $get)
                                    ),

                                Forms\Components\Select::make('unit')
                                    ->label('Hours / Days')
                                    ->options([
                                        'hour' => 'Hours',
                                        'day'  => 'Days',
                                    ])
                                    ->default('day')
                                    ->required(),
                            ]),

                        Forms\Components\TextInput::make('rate')
                            ->label('Rate')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($set, $get) =>
                                self::calculateSubTotal($set, $get)
                            ),
                    ])
                    ->reactive()
                    ->afterStateUpdated(fn ($set, $get) =>
                        self::calculateSubTotal($set, $get)
                    )
                    ->columnSpanFull(),

                /* ========== SUBTOTAL & TOTAL AMOUNT ========== */
                Forms\Components\TextInput::make('subtotal')
                    ->label('Subtotal')
                    ->default(0)
                    ->disabled()
                    ->reactive()
                    ->dehydrated()
                    ->columnSpanFull(),

                /* ========== ADVANCE PAYMENT ========== */
                Forms\Components\TextInput::make('advancePayment')
                    ->label('Advance Payment')
                    ->numeric()
                    ->reactive()
                    ->required()
                    ->columnSpanFull()
                    ->afterStateUpdated(fn ($set, $get) => self::calculateGrandTotal($set, $get)),

                /* ========== TOTAL AMOUNT ========== */
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->default(0)
                    ->numeric()
                    ->disabled()
                    ->reactive()
                    ->dehydrated()
                    ->columnSpanFull()
                    ->required(),

                Forms\Components\TextInput::make('total_paid')
                    ->label('Total Paid')
                    ->disabled()
                    ->reactive(),

                Forms\Components\TextInput::make('remaining_amount')
                    ->label('Final Due Amount')
                    ->disabled(),

                /* ========== TERMS & CONDITIONS ========== */
                Forms\Components\Textarea::make('terms')
                    ->label('Terms & Conditions')
                    ->rows(3)
                    ->placeholder('Enter payment terms, conditions, etc.')
                    ->columnSpanFull(),

                /* ========== DECLARATION ========== */
                Forms\Components\Textarea::make('declaration')
                    ->label('Declaration')
                    ->rows(3)
                    ->placeholder('Enter your declaration statement')
                    ->columnSpanFull(),
            ]);
    }

    // SUBTOTAL FROM ITEMS (for live UI update)
    public static function calculateSubTotal($set, $get): void
    {
        $items = $get('items') ?? [];

        $subtotal = collect($items)->sum(function ($item) {
            return ($item['quantity'] ?? 0) * ($item['rate'] ?? 0);
        });

        // save into subtotal (NOT amount)
        $set('subtotal', round($subtotal, 2));

        self::calculateGrandTotal($set, $get);
    }

    // GRAND TOTAL (for live UI update)
    public static function calculateGrandTotal($set, $get): void
    {
        $subtotal = floatval($get('subtotal') ?: 0);
        $gstType = $get('gst_type');

        $cgst = 0;
        $sgst = 0;
        $igst = 0;

        if ($gstType === 'cgst_sgst') {
            $cgst = $subtotal * floatval($get('gst_rate.cgst') ?: 0) / 100;
            $sgst = $subtotal * floatval($get('gst_rate.sgst') ?: 0) / 100;
        }

        if ($gstType === 'igst') {
            $igst = $subtotal * floatval($get('gst_rate.igst') ?: 0) / 100;
        }

        $grandTotal = $subtotal + $cgst + $sgst + $igst;

        $advance = floatval($get('advancePayment') ?: 0);
        $remaining = $grandTotal - $advance;

        // ✅ Store correct values
        $set('amount', round($grandTotal, 2));
        $set('remaining_amount', round($remaining, 2));
        $set('total_paid', round($advance, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_no')->searchable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer'),
                Tables\Columns\TextColumn::make('amount')->money('INR'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Download pdf')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Invoices $record) => route('invoice.pdf', $record))
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
