<?php

namespace App\Filament\Resources\Opportunities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OpportunitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('商机标题')->searchable()->sortable(),
                TextColumn::make('customer.name')->label('客户'),
                TextColumn::make('amount')->label('预计金额')->money('CNY')->sortable(),
                TextColumn::make('stage')->label('阶段')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'prospect' => 'info', 'proposal' => 'warning',
                        'negotiation' => 'primary', 'won' => 'success', 'lost' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'prospect' => '初步接触', 'proposal' => '方案报价',
                        'negotiation' => '谈判中', 'won' => '成交', 'lost' => '丢失',
                        default => $state,
                    }),
                TextColumn::make('probability')->label('成交率')->suffix('%'),
                TextColumn::make('expected_close_date')->label('预计成交')->date('Y-m-d')->sortable(),
                TextColumn::make('assignedUser.name')->label('负责人'),
            ])
            ->filters([
                SelectFilter::make('stage')->label('阶段')->options([
                    'prospect' => '初步接触', 'proposal' => '方案报价',
                    'negotiation' => '谈判中', 'won' => '成交', 'lost' => '丢失',
                ]),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
