<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiKeyResource\Pages;
use App\Models\ApiKey;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ApiKeyResource extends Resource
{
    protected static ?string $model = ApiKey::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = '系统';

    protected static ?string $navigationLabel = 'API Keys';

    protected static ?string $modelLabel = 'API Key';

    protected static ?string $pluralModelLabel = 'API Keys';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('user_id')
                ->label('所属用户')
                ->relationship('user', 'name')
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('name')
                ->label('用途说明')
                ->required()
                ->maxLength(100)
                ->placeholder('进销存小程序服务账号'),

            Forms\Components\TextInput::make('key')
                ->label('API Key')
                ->default(fn () => 'ak_' . Str::random(48))
                ->readOnly()
                ->helperText('自动生成，创建后请复制保存，不可再次查看完整值。')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('用户')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('用途说明')
                    ->searchable(),
                Tables\Columns\TextColumn::make('key')
                    ->label('Key（前缀）')
                    ->formatStateUsing(fn (string $state) => substr($state, 0, 12) . '…')
                    ->copyable()
                    ->copyableState(fn (ApiKey $record) => $record->key)
                    ->tooltip('点击复制完整 Key'),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('最后使用')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('从未使用')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->actions([
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListApiKeys::route('/'),
            'create' => Pages\CreateApiKey::route('/create'),
        ];
    }
}
