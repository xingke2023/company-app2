<?php

namespace App\Filament\Resources\Leads\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('name')->label('姓名')->required()->maxLength(100),
                    TextInput::make('company')->label('公司')->maxLength(255),
                    TextInput::make('phone')->label('电话')->tel()->maxLength(50),
                    TextInput::make('email')->label('邮箱')->email()->maxLength(255),
                    Select::make('source')->label('来源')
                        ->options([
                            'web' => '官网',
                            'referral' => '转介绍',
                            'call' => '电话',
                            'exhibition' => '展会',
                            'other' => '其他',
                        ])
                        ->default('other'),
                    Select::make('status')->label('状态')
                        ->options([
                            'new' => '新建',
                            'contacted' => '已联系',
                            'qualified' => '已确认',
                            'converted' => '已转化',
                            'lost' => '已丢失',
                        ])
                        ->default('new')->required(),
                    Select::make('assigned_to')->label('负责人')
                        ->relationship('assignedUser', 'name')
                        ->searchable()->preload(),
                    DateTimePicker::make('last_contacted_at')->label('最后联系时间'),
                    Textarea::make('notes')->label('备注')->rows(3)->columnSpan(2),
                ]),
            ]);
    }
}
