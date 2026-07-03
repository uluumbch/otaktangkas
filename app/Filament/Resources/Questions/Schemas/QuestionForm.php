<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Enums\Difficulty;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                Textarea::make('question')
                    ->required()
                    ->columnSpanFull(),
                Select::make('difficulty')
                    ->options(Difficulty::class)
                    ->default('medium')
                    ->required(),
                TextInput::make('language')
                    ->required()
                    ->default('id'),
                TextInput::make('time_limit')
                    ->required()
                    ->numeric()
                    ->default(30),
                TextInput::make('xp_reward')
                    ->required()
                    ->numeric()
                    ->default(10),
                TextInput::make('coins_reward')
                    ->required()
                    ->numeric()
                    ->default(5),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('times_used')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('times_correct')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('times_incorrect')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('explanation')
                    ->columnSpanFull(),
                Textarea::make('tags')
                    ->columnSpanFull(),
            ]);
    }
}
