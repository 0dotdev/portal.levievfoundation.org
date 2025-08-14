<?php

namespace App\Filament\Resources\Admin;

use App\Filament\Resources\Admin\UserResource\Pages;
use App\Filament\Resources\Admin\UserResource\RelationManagers;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $slug = 'users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required(),
                TextInput::make('email')->email()->required(),
                TextInput::make('password')->password()->visibleOn('create')->required(),
                Select::make('roles')
                    ->label('Role')
                    ->options([
                        'user' => 'User',
                        'admin' => 'Admin',
                    ])
                    ->native(false)
                    ->default('user')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('created_at')->searchable()->sortable()->date(),
                TextColumn::make('roles')->label('Role')->searchable()->sortable()->badge()
                    ->getStateUsing(function ($record) {
                        $role = $record->roles;
                        if ($role == 'user') {
                            return 'User';
                        } else if ($role == 'admin') {
                            return 'Admin';
                        }
                    })
                    ->color(function ($record) {
                        $role = $record->role;
                        if ($role == 'user') {
                            return 'warning';
                        } else if ($role == 'admin') {
                            return 'success';
                        }
                    }),
                TextColumn::make('email_verified_at')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->getStateUsing(function ($record) {
                        return $record->email_verified_at ? 'Verified' : 'Not Verified';
                    })
                    ->color(function ($record) {
                        return $record->email_verified_at ? 'success' : 'danger';
                    }),

            ])->defaultSort('created_at', 'desc')->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
