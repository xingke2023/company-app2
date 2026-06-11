<?php

namespace App\Filament\Resources\Activities\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Select::make('type')->label('活动类型')
                        ->options([
                            'call' => '电话',
                            'meeting' => '会议',
                            'email' => '邮件',
                            'visit' => '拜访',
                            'note' => '备注',
                        ])
                        ->required()->default('call'),
                    TextInput::make('title')->label('标题')->required()->maxLength(255),
                    Select::make('customer_id')->label('关联客户')
                        ->relationship('customer', 'name')
                        ->searchable()->preload(),
                    Select::make('lead_id')->label('关联线索')
                        ->relationship('lead', 'name')
                        ->searchable()->preload(),
                    Select::make('opportunity_id')->label('关联商机')
                        ->relationship('opportunity', 'title')
                        ->searchable()->preload(),
                    Select::make('user_id')->label('负责人')
                        ->relationship('user', 'name')
                        ->searchable()->preload()->required(),
                    DateTimePicker::make('scheduled_at')->label('计划时间'),
                    DateTimePicker::make('completed_at')->label('完成时间'),
                    Textarea::make('description')->label('描述')->rows(4)->columnSpan(2),
                ]),
            ]);
    }
}
