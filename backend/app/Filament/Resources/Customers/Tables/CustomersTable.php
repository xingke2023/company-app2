<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('客户姓名')->searchable()->sortable(),
                TextColumn::make('company')->label('公司')->searchable(),
                TextColumn::make('phone')->label('电话'),
                TextColumn::make('email')->label('邮箱'),
                TextColumn::make('type')->label('类型')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'individual' => '个人',
                        'company' => '企业',
                        default => $state,
                    }),
                TextColumn::make('status')->label('状态')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'potential' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'active' => '正常',
                        'inactive' => '停用',
                        'potential' => '潜在',
                        default => $state,
                    }),
                TextColumn::make('assignedUser.name')->label('负责人'),
                TextColumn::make('created_at')->label('创建时间')->dateTime('Y-m-d')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('状态')->options([
                    'active' => '正常',
                    'inactive' => '停用',
                    'potential' => '潜在',
                ]),
                SelectFilter::make('type')->label('类型')->options([
                    'individual' => '个人',
                    'company' => '企业',
                ]),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
