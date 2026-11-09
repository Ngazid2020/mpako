<?php

namespace App\Filament\Admin\Resources\WalletResource\Actions;

use App\Models\Wallet;
use Filament\Tables\Actions\Action;

class ApproveAction
{
    public static function make(): Action
    {
        return Action::make('approve')
            ->label('Valider')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn(Wallet $wallet) => $wallet->canBeValidated() && auth()->user()?->can('approve', $wallet))
            ->requiresConfirmation()
            ->modalHeading('Valider le portefeuille')
            ->modalDescription('Le portefeuille sera marqué comme validé et activé.')
            ->modalSubmitActionLabel('Valider')
            ->authorize(fn(Wallet $wallet) => auth()->user()?->can('approve', $wallet))
            ->action(function (Wallet $wallet, array $data): void {
                $userId = auth()?->id();
                $notes = $data['notes'] ?? '';
                
                $wallet->approve($userId, $notes);
            })
            ->form([
                \Filament\Forms\Components\Textarea::make('notes')
                    ->label('Notes (optionnel)')
                    ->rows(2)
                    ->hint('Ajoutez un commentaire pour le propriétaire'),
            ]);
    }
}
