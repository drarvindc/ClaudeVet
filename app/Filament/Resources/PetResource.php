<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PetResource\Pages;
use App\Models\Pet;
use App\Models\Owner;
use App\Models\Species;
use App\Models\Breed;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PetResource extends Resource
{
    protected static ?string $model = Pet::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Pets';

    protected static ?string $navigationGroup = 'Patient Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('unique_id')
                            ->label('Unique ID')
                            ->required()
                            ->unique(ignoreDuplicates: true)
                            ->maxLength(6)
                            ->placeholder('250001'),
                        
                        Forms\Components\Select::make('owner_id')
                            ->label('Owner')
                            ->relationship('owner', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn (Owner $record): string => $record->full_name)
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        Forms\Components\TextInput::make('pet_name')
                            ->label('Pet Name')
                            ->maxLength(80),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'provisional' => 'Provisional',
                                'archived' => 'Archived',
                            ])
                            ->default('provisional')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Physical Details')
                    ->schema([
                        Forms\Components\Select::make('species_id')
                            ->label('Species')
                            ->relationship('species', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('breed_id', null);
                            }),
                        
                        Forms\Components\Select::make('breed_id')
                            ->label('Breed')
                            ->relationship(
                                name: 'breed',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query, Forms\Get $get): Builder => 
                                    $query->where('species_id', $get('species_id'))
                            )
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'unknown' => 'Unknown',
                            ])
                            ->default('unknown'),
                        
                        Forms\Components\TextInput::make('color')
                            ->maxLength(50),
                    ])->columns(2),

                Forms\Components\Section::make('Age Information')
                    ->description('Enter either date of birth OR age in years/months')
                    ->schema([
                        Forms\Components\DatePicker::make('dob')
                            ->label('Date of Birth')
                            ->native(false),
                        
                        Forms\Components\TextInput::make('age_years')
                            ->label('Age (Years)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(30),
                        
                        Forms\Components\TextInput::make('age_months')
                            ->label('Age (Months)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(11),
                    ])->columns(3),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\TextInput::make('microchip')
                            ->label('Microchip Number')
                            ->maxLength(32),
                        
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('unique_id')
                    ->label('UID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono'),
                
                Tables\Columns\TextColumn::make('pet_name')
                    ->label('Pet Name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Unnamed'),
                
                Tables\Columns\TextColumn::make('owner.full_name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('species.display_name')
                    ->label('Species')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('breed.name')
                    ->label('Breed')
                    ->placeholder('Mixed/Unknown'),
                
                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'male' => 'blue',
                        'female' => 'pink',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('formatted_age')
                    ->label('Age'),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'provisional' => 'warning',
                        'archived' => 'gray',
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
                        'archived' => 'Archived',
                    ]),
                Tables\Filters\SelectFilter::make('species_id')
                    ->relationship('species', 'name')
                    ->label('Species'),
                Tables\Filters\SelectFilter::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                        'unknown' => 'Unknown',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('letterhead')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Pet $record): string => route('patient.letterhead', $record->unique_id))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListPets::route('/'),
            'create' => Pages\CreatePet::route('/create'),
            'edit' => Pages\EditPet::route('/{record}/edit'),
        ];
    }
}