<?php

namespace App\Filament\Resources\Leads\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('姓名')->searchable()->sortable(),
                TextColumn::make('company')->label('公司')->searchable(),
                TextColumn::make('phone')->label('电话'),
                TextColumn::make('source')->label('来源')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'web' => '官网', 'referral' => '转介绍', 'call' => '电话',
                        'exhibition' => '展会', 'other' => '其他', default => $state,
                    }),
                TextColumn::make('status')->label('状态')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'new' => 'info', 'contacted' => 'warning', 'qualified' => 'success',
                        'converted' => 'primary', 'lost' => 'danger', default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'new' => '新建', 'contacted' => '已联系', 'qualified' => '已确认',
                        'converted' => '已转化', 'lost' => '已丢失', default => $state,
                    }),
                TextColumn::make('assignedUser.name')->label('负责人'),
                TextColumn::make('last_contacted_at')->label('最后联系')->dateTime('Y-m-d')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('状态')->options([
                    'new' => '新建', 'contacted' => '已联系', 'qualified' => '已确认',
                    'converted' => '已转化', 'lost' => '已丢失',
                ]),
                SelectFilter::make('source')->label('来源')->options([
                    'web' => '官网', 'referral' => '转介绍', 'call' => '电话',
                    'exhibition' => '展会', 'other' => '其他',
                ]),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
