<?php

namespace App\Filament\Resources\PurchaseOrders\RelationManagers;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = '采购明细';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('商品')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $product = Product::find($state);
                            if ($product) {
                                $set('unit_price', $product->cost_price);
                            }
                        }
                    }),
                TextInput::make('quantity')->label('数量')->numeric()->integer()->required()->default(1)->minValue(1),
                TextInput::make('unit_price')->label('单价')->numeric()->prefix('¥')->required(),
                TextInput::make('total_price')->label('合计')->numeric()->prefix('¥')->disabled()->dehydrated(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                TextColumn::make('product.name')->label('商品'),
                TextColumn::make('product.sku')->label('SKU'),
                TextColumn::make('quantity')->label('数量'),
                TextColumn::make('unit_price')->label('单价')->money('CNY'),
                TextColumn::make('total_price')->label('合计')->money('CNY'),
            ])
            ->filters([])
            ->headerActions([CreateAction::make()->label('添加明细')])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
