<?php

namespace App\Filament\Admin\Resources\WalletResource\Actions;

use App\Models\Wallet;
use Filament\Tables\Actions\Action;

class RejectAction
{
    public static function make(): Action
    {
        return Action::make('reject')
            ->label('Rejeter')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn(Wallet $wallet) => $wallet->canBeValidated() && auth()->user()?->can('reject', $wallet))
            ->requiresConfirmation()
            ->modalHeading('Rejeter le portefeuille')
            ->modalDescription('Le portefeuille sera marqué comme rejeté et désactivé.')
            ->modalSubmitActionLabel('Rejeter')
            ->authorize(fn(Wallet $wallet) => auth()->user()?->can('reject', $wallet))
            ->action(function (Wallet $wallet, array $data): void {
                $userId = auth()?->id();
                $reason = $data['reason'] ?? '';
                
                $wallet->reject($userId, $reason);
            })
            ->form([
                \Filament\Forms\Components\Textarea::make('reason')
                    ->label('Raison du rejet')
                    ->rows(3)
                    ->required()
                    ->hint('Expliquez pourquoi le portefeuille est rejeté'),
            ]);
    }
}
