<?php

namespace App\Filament\Resources\Activities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->label('类型')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'call' => 'info', 'meeting' => 'warning', 'email' => 'primary',
                        'visit' => 'success', 'note' => 'gray', default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'call' => '电话', 'meeting' => '会议', 'email' => '邮件',
                        'visit' => '拜访', 'note' => '备注', default => $state,
                    }),
                TextColumn::make('title')->label('标题')->searchable()->sortable(),
                TextColumn::make('customer.name')->label('客户'),
                TextColumn::make('user.name')->label('负责人'),
                TextColumn::make('scheduled_at')->label('计划时间')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('completed_at')->label('完成时间')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->label('类型')->options([
                    'call' => '电话', 'meeting' => '会议', 'email' => '邮件',
                    'visit' => '拜访', 'note' => '备注',
                ]),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
