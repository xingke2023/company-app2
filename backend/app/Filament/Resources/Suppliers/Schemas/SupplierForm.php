<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('name')->label('供应商名称')->required()->maxLength(255)->columnSpan(2),
                    TextInput::make('contact_person')->label('联系人')->maxLength(100),
                    TextInput::make('phone')->label('电话')->tel()->maxLength(50),
                    TextInput::make('email')->label('邮箱')->email()->maxLength(255),
                    Select::make('status')->label('状态')
                        ->options(['active' => '正常', 'inactive' => '停用'])
                        ->default('active')->required(),
                    TextInput::make('address')->label('地址')->maxLength(500)->columnSpan(2),
                    Textarea::make('notes')->label('备注')->rows(3)->columnSpan(2),
                ]),
            ]);
    }
}
