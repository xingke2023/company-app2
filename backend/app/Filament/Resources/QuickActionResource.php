<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuickActionResource\Pages;
use App\Filament\Resources\QuickActionResource\RelationManagers\ItemsRelationManager;
use App\Models\QuickAction;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class QuickActionResource extends Resource
{
    protected static ?string $model = QuickAction::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|UnitEnum|null $navigationGroup = '小程序菜单';

    protected static ?string $navigationLabel = '快捷按钮';

    protected static ?string $modelLabel = '快捷按钮';

    protected static ?string $pluralModelLabel = '快捷按钮';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('action_type')
                    ->label('按钮类型')
                    ->options([
                        'external' => '打开 app2 网页（external，完整 URL）',
                        'open' => '打开网页（open，相对路径）',
                        'menu' => '弹出子菜单（menu）',
                        'home' => '返回主页 / 切换行业（home）',
                        'prompt' => '发送文字给 AI（prompt）',
                        'web' => '发 AI 摘要 + 打开网页（web）',
                    ])
                    ->default('external')
                    ->required()
                    ->live()
                    ->helperText('进销存/CRM 推荐用 external，target_path 填完整后台 URL；menu 的子项在下方维护'),

                Forms\Components\TextInput::make('emoji')
                    ->label('图标 Emoji')
                    ->maxLength(16)
                    ->placeholder('📦'),

                Forms\Components\TextInput::make('label')
                    ->label('按钮文字')
                    ->required()
                    ->maxLength(50),

                Forms\Components\TextInput::make('key')
                    ->label('标识 key')
                    ->required()
                    ->maxLength(50)
                    ->helperText('英文唯一标识，用作样式类 qa-chip-{key}'),

                Forms\Components\TextInput::make('target_path')
                    ->label('网页地址 / 路径')
                    ->maxLength(300)
                    ->placeholder('https://app2.xingke888.com/admin/products')
                    ->helperText('external 填完整 URL；open 填相对路径；home 填回跳路径（留空=默认落地页）')
                    ->visible(fn (Get $get) => in_array($get('action_type'), ['external', 'open', 'home']))
                    ->required(fn (Get $get) => in_array($get('action_type'), ['external', 'open'])),

                Forms\Components\TextInput::make('target_title')
                    ->label('网页标题')
                    ->maxLength(50)
                    ->visible(fn (Get $get) => in_array($get('action_type'), ['open', 'web'])),

                Forms\Components\Textarea::make('prompt')
                    ->label('发给 AI 的文字')
                    ->rows(2)
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => in_array($get('action_type'), ['prompt', 'web']))
                    ->required(fn (Get $get) => in_array($get('action_type'), ['prompt', 'web'])),

                Forms\Components\TextInput::make('badge')
                    ->label('角标文字')
                    ->maxLength(20)
                    ->helperText('留空则不显示角标'),

                Forms\Components\TextInput::make('sort_order')
                    ->label('排序（小在前）')
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('enabled')
                    ->label('启用')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('emoji')->label('图标'),
                Tables\Columns\TextColumn::make('label')->label('按钮文字')->searchable(),
                Tables\Columns\TextColumn::make('action_type')
                    ->label('类型')
                    ->badge()
                    ->formatStateUsing(fn ($state) => [
                        'external' => '打开网页',
                        'open' => '打开网页',
                        'menu' => '子菜单',
                        'home' => '返回主页',
                        'prompt' => '文字',
                        'web' => '摘要+网页',
                    ][$state] ?? $state),
                Tables\Columns\TextColumn::make('key')->label('key')->toggleable(),
                Tables\Columns\IconColumn::make('enabled')->label('启用')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label('排序')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('enabled')->label('启用'),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuickActions::route('/'),
            'create' => Pages\CreateQuickAction::route('/create'),
            'edit' => Pages\EditQuickAction::route('/{record}/edit'),
        ];
    }
}
