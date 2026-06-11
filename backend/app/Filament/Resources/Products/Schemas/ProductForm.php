<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('name')->label('商品名称')->required()->maxLength(255)->columnSpan(2),
                    TextInput::make('sku')->label('SKU编码')->required()->maxLength(100)->unique(ignoreRecord: true),
                    TextInput::make('unit')->label('单位')->default('件')->maxLength(20),
                    Select::make('category_id')->label('商品分类')
                        ->relationship('category', 'name')
                        ->searchable()->preload(),
                    Select::make('supplier_id')->label('供应商')
                        ->relationship('supplier', 'name')
                        ->searchable()->preload(),
                    TextInput::make('cost_price')->label('成本价')->numeric()->prefix('¥')->default(0),
                    TextInput::make('sell_price')->label('售价')->numeric()->prefix('¥')->default(0),
                    TextInput::make('stock_quantity')->label('库存数量')->numeric()->integer()->default(0),
                    TextInput::make('min_stock')->label('最低库存')->numeric()->integer()->default(0),
                    Select::make('status')->label('状态')
                        ->options(['active' => '在售', 'inactive' => '下架'])
                        ->default('active')->required(),
                    Textarea::make('description')->label('商品描述')->rows(3)->columnSpan(2),
                ]),
            ]);
    }
}
