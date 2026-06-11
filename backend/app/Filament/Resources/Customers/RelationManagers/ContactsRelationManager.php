<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $title = '联系人';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('姓名')->required()->maxLength(100),
                TextInput::make('position')->label('职位')->maxLength(100),
                TextInput::make('phone')->label('电话')->tel()->maxLength(50),
                TextInput::make('email')->label('邮箱')->email()->maxLength(255),
                Checkbox::make('is_primary')->label('主要联系人'),
                Textarea::make('notes')->label('备注')->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('姓名'),
                TextColumn::make('position')->label('职位'),
                TextColumn::make('phone')->label('电话'),
                TextColumn::make('email')->label('邮箱'),
                IconColumn::make('is_primary')->label('主联系人')->boolean(),
            ])
            ->filters([])
            ->headerActions([CreateAction::make()->label('添加联系人')])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
