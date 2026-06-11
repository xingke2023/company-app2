<?php

namespace App\Filament\Resources\ProductCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('分类名称')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('描述')
                    ->rows(3),
            ]);
    }
}
