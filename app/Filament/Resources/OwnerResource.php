<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OwnerResource\Pages;
use App\Models\Owner;
use App\Models\OwnerMobile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OwnerResource extends Resource
{
    protected static ?string $model = Owner::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Owners';

    protected static ?string $navigationGroup = 'Patient Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(60),
                        
                        Forms\Components\TextInput::make('middle_name')
                            ->maxLength(60),
                        
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(60),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'provisional' => 'Provisional',
                                'merged' => 'Merged',
                            ])
                            ->default('provisional')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\Repeater::make('mobiles')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('mobile_e164')
                                    ->label('Mobile Number')
                                    ->tel()
                                    ->required()
                                    ->maxLength(20)
                                    ->placeholder('+919876543210')
                                    ->helperText('Include country code'),
                                
                                Forms\Components\Toggle::make('is_primary')
                                    ->label('Primary Number')
                                    ->default(false),
                                
                                Forms\Components\Toggle::make('is_verified')
                                    ->label('Verified')
                                    ->default(false),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Add Mobile Number')
                            ->collapsible(),
                        
                        Forms\Components\TextInput::make('locality')
                            ->maxLength(120),
                        
                        Forms\Components\Textarea::make('address')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'middle_name', 'last_name'])
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('primary_mobile_number')
                    ->label('Primary Mobile')
                    ->placeholder('Not set')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('pets_count')
                    ->label('Pets')
                    ->counts('pets')
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('locality')
                    ->placeholder('Not specified')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'provisional' => 'warning',
                        'merged' => 'gray',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'provisional' => 'Provisional',
                        'merged' => 'Merged',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_pets')
                    ->label('View Pets')
                    ->icon('heroicon-o-heart')
                    ->url(fn (Owner $record): string => "/admin/pets?tableFilters[owner_id][value]={$record->id}")
                    ->visible(fn (Owner $record): bool => $record->pets->count() > 0),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOwners::route('/'),
            'create' => Pages\CreateOwner::route('/create'),
            'edit' => Pages\EditOwner::route('/{record}/edit'),
        ];
    }
}