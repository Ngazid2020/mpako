<?php

namespace App\Filament\Admin\Resources\WalletResource\Actions;

use App\Models\Wallet;
use Filament\Tables\Actions\Action;

class SubmitAction
{
    public static function make(): Action
    {
        return Action::make('submit')
            ->label('Soumettre')
            ->icon('heroicon-o-paper-airplane')
            ->color('warning')
            ->visible(fn(Wallet $wallet) => $wallet->canBeSubmitted() && auth()->user()?->can('submit', $wallet))
            ->requiresConfirmation()
            ->modalHeading('Soumettre le portefeuille pour validation')
            ->modalDescription('Le portefeuille sera envoyé à l\'administrateur pour validation.')
            ->modalSubmitActionLabel('Confirmer la soumission')
            ->authorize(fn(Wallet $wallet) => auth()->user()?->can('submit', $wallet))
            ->action(function (Wallet $wallet, array $data): void {
                $userId = auth()?->id();
                $notes = $data['notes'] ?? '';
                
                $wallet->submitForValidation($userId, $notes);
            })
            ->form([
                \Filament\Forms\Components\Textarea::make('notes')
                    ->label('Notes (optionnel)')
                    ->rows(2)
                    ->hint('Ajoutez un message pour l\'administrateur'),
            ]);
    }
}
