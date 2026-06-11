<?php

namespace App\Filament\Resources\PurchaseOrders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class PurchaseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('order_no')->label('采购单号')->required()->maxLength(50)
                        ->default(fn () => 'PO-'.date('YmdHis')),
                    Select::make('supplier_id')->label('供应商')
                        ->relationship('supplier', 'name')
                        ->searchable()->preload()->required(),
                    Select::make('status')->label('状态')
                        ->options([
                            'draft' => '草稿',
                            'confirmed' => '已确认',
                            'received' => '已收货',
                            'cancelled' => '已取消',
                        ])
                        ->default('draft')->required(),
                    TextInput::make('total_amount')->label('总金额')->numeric()->prefix('¥')->default(0),
                    DateTimePicker::make('ordered_at')->label('下单时间'),
                    DateTimePicker::make('received_at')->label('收货时间'),
                    Textarea::make('notes')->label('备注')->rows(3)->columnSpan(2),
                ]),
            ]);
    }
}
