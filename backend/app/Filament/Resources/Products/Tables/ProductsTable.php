<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')->label('SKU')->searchable()->sortable(),
                TextColumn::make('name')->label('商品名称')->searchable()->sortable(),
                TextColumn::make('category.name')->label('分类'),
                TextColumn::make('supplier.name')->label('供应商'),
                TextColumn::make('sell_price')->label('售价')->money('CNY')->sortable(),
                TextColumn::make('stock_quantity')->label('库存')->sortable()
                    ->color(fn ($record) => $record?->isLowStock() ? 'danger' : 'success'),
                TextColumn::make('status')->label('状态')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'active' => '在售',
                        'inactive' => '下架',
                        default => $state,
                    }),
                TextColumn::make('updated_at')->label('更新时间')->dateTime('Y-m-d')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('状态')
                    ->options(['active' => '在售', 'inactive' => '下架']),
                SelectFilter::make('category_id')->label('分类')
                    ->relationship('category', 'name'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
