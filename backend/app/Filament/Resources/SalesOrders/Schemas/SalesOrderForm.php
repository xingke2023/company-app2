<?php

namespace App\Filament\Resources\SalesOrders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class SalesOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('order_no')->label('销售单号')->required()->maxLength(50)
                        ->default(fn () => 'SO-'.date('YmdHis')),
                    Select::make('customer_id')->label('客户')
                        ->relationship('customer', 'name')
                        ->searchable()->preload()->required(),
                    Select::make('status')->label('状态')
                        ->options([
                            'draft' => '草稿',
                            'confirmed' => '已确认',
                            'shipped' => '已发货',
                            'completed' => '已完成',
                            'cancelled' => '已取消',
                        ])
                        ->default('draft')->required(),
                    TextInput::make('total_amount')->label('总金额')->numeric()->prefix('¥')->default(0),
                    DateTimePicker::make('ordered_at')->label('下单时间'),
                    DateTimePicker::make('shipped_at')->label('发货时间'),
                    DateTimePicker::make('completed_at')->label('完成时间'),
                    Textarea::make('notes')->label('备注')->rows(3)->columnSpan(2),
                ]),
            ]);
    }
}
