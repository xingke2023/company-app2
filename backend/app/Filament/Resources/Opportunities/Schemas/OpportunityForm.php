<?php

namespace App\Filament\Resources\Opportunities\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class OpportunityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('title')->label('商机标题')->required()->maxLength(255)->columnSpan(2),
                    Select::make('customer_id')->label('客户')
                        ->relationship('customer', 'name')
                        ->searchable()->preload(),
                    Select::make('lead_id')->label('关联线索')
                        ->relationship('lead', 'name')
                        ->searchable()->preload(),
                    TextInput::make('amount')->label('预计金额')->numeric()->prefix('¥')->default(0),
                    Select::make('stage')->label('阶段')
                        ->options([
                            'prospect' => '初步接触',
                            'proposal' => '方案报价',
                            'negotiation' => '谈判中',
                            'won' => '成交',
                            'lost' => '丢失',
                        ])
                        ->default('prospect')->required(),
                    TextInput::make('probability')->label('成交概率%')->numeric()->integer()->default(0)->minValue(0)->maxValue(100),
                    DatePicker::make('expected_close_date')->label('预计成交日期'),
                    Select::make('assigned_to')->label('负责人')
                        ->relationship('assignedUser', 'name')
                        ->searchable()->preload(),
                    Textarea::make('notes')->label('备注')->rows(3)->columnSpan(2),
                ]),
            ]);
    }
}
