<?php

namespace App\Filament\Resources\PurchaseOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_no')->label('采购单号')->searchable()->sortable(),
                TextColumn::make('supplier.name')->label('供应商')->searchable(),
                TextColumn::make('status')->label('状态')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'draft' => 'gray',
                        'confirmed' => 'info',
                        'received' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'draft' => '草稿',
                        'confirmed' => '已确认',
                        'received' => '已收货',
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
                    'received' => '已收货',
                    'cancelled' => '已取消',
                ]),
                SelectFilter::make('supplier_id')->label('供应商')->relationship('supplier', 'name'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
