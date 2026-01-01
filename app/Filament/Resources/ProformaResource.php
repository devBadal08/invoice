<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProformaResource\Pages;
use App\Filament\Resources\ProformaResource\RelationManagers;
use App\Models\ProformaInvoices;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Company;

class ProformaResource extends Resource
{
    protected static ?string $model = ProformaInvoices::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Proforma Invoices';
    protected static ?string $navigationGroup = 'Invoices Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('proforma_invoice_no')
                    ->label('Proforma No')
                    ->content(fn ($record) =>
                        $record?->proforma_invoice_no ?? ProformaInvoices::previewNextProformaNumber()
                ),

                Forms\Components\DatePicker::make('invoice_date')
                    ->label('Invoice Date')
                    ->default(now()),

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
                        Forms\Components\TextInput::make('description'),

                        Forms\Components\TextInput::make('qty')
                            ->numeric()
                            ->default(1)
                            ->reactive(),

                        Forms\Components\TextInput::make('rate')
                            ->numeric()
                            ->reactive(),
                    ])
                    ->reactive()
                    ->afterStateUpdated(fn ($set, $get) => self::calculateSubTotal($set, $get))
                    ->columns(3)
                    ->columnSpanFull(),

                /* ========== SUBTOTAL & TOTAL AMOUNT ========== */
                Forms\Components\TextInput::make('subtotal')
                    ->label('Subtotal')
                    ->disabled()
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
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->columnSpanFull()
                    ->required(),

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
            return ($item['qty'] ?? 0) * ($item['rate'] ?? 0);
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

        if ($gstType === 'no_gst') {
            $total = $subtotal;
        }
        elseif ($gstType === 'cgst_sgst') {
            $cgstRate = $get('gst_rate.cgst') ?? 0;
            $sgstRate = $get('gst_rate.sgst') ?? 0;

            $cgst = ($subtotal * $cgstRate) / 100;
            $sgst = ($subtotal * $sgstRate) / 100;

            $total = $subtotal + $cgst + $sgst;
        }
        else { // igst
            $igstRate = $get('gst_rate.igst') ?? 0;
            $igst = ($subtotal * $igstRate) / 100;

            $total = $subtotal + $igst;
        }

        // IMPORTANT: amount = GRAND TOTAL (NO advance subtraction)
        $set('amount', round($total, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('proforma_invoice_no')->label('Proforma No'),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer'),
                Tables\Columns\TextColumn::make('amount')->money('INR'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('invoices')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => route('proforma.download', $record->id))
                    ->openUrlInNewTab(),
            ])
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
            'index' => Pages\ListProformas::route('/'),
            'create' => Pages\CreateProforma::route('/create'),
            'edit' => Pages\EditProforma::route('/{record}/edit'),
        ];
    }
}
