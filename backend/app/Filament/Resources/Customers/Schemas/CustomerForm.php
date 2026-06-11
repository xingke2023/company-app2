<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('name')->label('客户姓名')->required()->maxLength(100),
                    TextInput::make('company')->label('公司')->maxLength(255),
                    TextInput::make('phone')->label('电话')->tel()->maxLength(50),
                    TextInput::make('email')->label('邮箱')->email()->maxLength(255),
                    Select::make('type')->label('客户类型')
                        ->options(['individual' => '个人', 'company' => '企业'])
                        ->default('individual')->required(),
                    Select::make('source')->label('来源')
                        ->options([
                            'web' => '官网',
                            'referral' => '转介绍',
                            'call' => '电话',
                            'exhibition' => '展会',
                            'other' => '其他',
                        ]),
                    Select::make('status')->label('状态')
                        ->options([
                            'active' => '正常',
                            'inactive' => '停用',
                            'potential' => '潜在',
                        ])
                        ->default('active')->required(),
                    Select::make('assigned_to')->label('负责人')
                        ->relationship('assignedUser', 'name')
                        ->searchable()->preload(),
                    TextInput::make('address')->label('地址')->maxLength(500)->columnSpan(2),
                    Textarea::make('notes')->label('备注')->rows(3)->columnSpan(2),
                ]),
            ]);
    }
}
