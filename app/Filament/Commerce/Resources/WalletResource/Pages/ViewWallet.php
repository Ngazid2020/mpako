<?php

namespace App\Filament\Commerce\Resources\WalletResource\Pages;

use App\Filament\Commerce\Resources\WalletResource;
use App\Models\Wallet;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewWallet extends ViewRecord
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Soumettre pour validation')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible(fn(Wallet $wallet) => $wallet->canBeSubmitted() && auth()->user()?->can('submit', $wallet))
                ->requiresConfirmation()
                ->modalHeading('Soumettre le portefeuille pour validation')
                ->modalDescription('Le portefeuille sera envoyé à l\'administrateur pour validation.')
                ->modalSubmitActionLabel('Confirmer la soumission')
                ->action(function (Wallet $wallet, array $data): void {
                    $userId = auth()?->id();
                    $notes = $data['notes'] ?? '';
                    
                    $wallet->submitForValidation($userId, $notes);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Portefeuille soumis avec succès pour validation.')
                        ->success()
                        ->send();
                })
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Notes (optionnel)')
                        ->rows(2)
                        ->hint('Ajoutez un message pour l\'administrateur'),
                ]),
        ];
    }
}
