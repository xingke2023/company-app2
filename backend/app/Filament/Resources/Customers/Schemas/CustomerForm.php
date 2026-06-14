<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
                Section::make('客户画像')->schema([
                    Grid::make(3)->schema([
                        Select::make('gender')->label('性别')
                            ->options(['male' => '男', 'female' => '女', 'other' => '其他']),
                        Select::make('age_group')->label('年龄段')
                            ->options([
                                '18-25' => '18-25',
                                '26-35' => '26-35',
                                '36-45' => '36-45',
                                '46-55' => '46-55',
                                '55+' => '55+',
                            ]),
                        DatePicker::make('birthday')->label('生日'),
                        TextInput::make('city')->label('城市')->maxLength(100),
                        TextInput::make('industry')->label('所在行业')->maxLength(100),
                        Select::make('income_level')->label('收入水平')
                            ->options([
                                'low' => '低',
                                'medium' => '中',
                                'high' => '高',
                                'ultra-high' => '超高净值',
                            ]),
                    ]),
                    TagsInput::make('hobbies')->label('爱好')->placeholder('回车添加')->columnSpanFull(),
                    TagsInput::make('tags')->label('标签')->placeholder('如 VIP、价格敏感、高复购')->columnSpanFull(),
                ])->collapsible(),
            ]);
    }
}
