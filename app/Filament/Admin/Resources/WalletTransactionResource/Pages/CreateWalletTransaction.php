<?php

namespace App\Filament\Admin\Resources\WalletTransactionResource\Pages;

use App\Filament\Admin\Resources\WalletTransactionResource;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateWalletTransaction extends CreateRecord
{
    protected static string $resource = WalletTransactionResource::class;

    protected function afterFill(): void
    {
        // Pré-sélection depuis le bouton « Recharger » de ShopResource (?wallet_id=)
        if ($walletId = request()->integer('wallet_id')) {
            $this->form->fill(['wallet_id' => $walletId] + $this->form->getRawState());
        }
    }

    /**
     * Recharge = Wallet::credit() : verrou, solde relu en base, journal + solde atomiques.
     */
    protected function handleRecordCreation(array $data): WalletTransaction
    {
        $wallet = Wallet::find($data['wallet_id']);

        if (! $wallet) {
            throw ValidationException::withMessages(['data.wallet_id' => 'Portefeuille introuvable.']);
        }

        if ((float) $data['amount'] <= 0) {
            throw ValidationException::withMessages(['data.amount' => 'Le montant doit être positif.']);
        }

        return $wallet->credit(
            (float) $data['amount'],
            $data['payment_method'],
            $data['notes'] ?? '',
            Auth::id(),
        );
    }
}
