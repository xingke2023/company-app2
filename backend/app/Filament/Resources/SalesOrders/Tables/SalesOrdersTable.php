<?php

namespace App\Filament\Resources\SalesOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_no')->label('销售单号')->searchable()->sortable(),
                TextColumn::make('customer.name')->label('客户')->searchable(),
                TextColumn::make('status')->label('状态')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'draft' => 'gray',
                        'confirmed' => 'info',
                        'shipped' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'draft' => '草稿',
                        'confirmed' => '已确认',
                        'shipped' => '已发货',
                        'completed' => '已完成',
                        'cancelled' => '已取消',
                        default => $state,
                    }),
                TextColumn::make('total_amount')->label('总金额')->money('CNY')->sortable(),
                TextColumn::make('ordered_at')->label('下单时间')->dateTime('Y-m-d')->sortable(),
                TextColumn::make('created_at')->label('创建时间')->dateTime('Y-m-d')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('状态')->options([
                    'draft' => '草稿',
                    'confirmed' => '已确认',
                    'shipped' => '已发货',
                    'completed' => '已完成',
                    'cancelled' => '已取消',
                ]),
                SelectFilter::make('customer_id')->label('客户')->relationship('customer', 'name'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
