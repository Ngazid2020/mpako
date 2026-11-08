<?php

namespace App\Filament\Admin\Resources\WalletTransactionResource\Pages;

use App\Filament\Admin\Resources\WalletTransactionResource;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWalletTransaction extends CreateRecord
{
    protected static string $resource = WalletTransactionResource::class;

    protected function handleRecordCreation(array $data): WalletTransaction
    {
        $wallet = Wallet::find($data['wallet_id']);
        if (!$wallet) {
            throw new \Exception('Portefeuille introuvable');
        }

        $oldBalance = $wallet->balance;
        $newBalance = $oldBalance + $data['amount'];
        $reference = WalletTransaction::generateReference($wallet->shop_id);

        $transaction = WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'shop_id' => $wallet->shop_id,
            'user_id' => Auth::id(),
            'reference' => $reference,
            'type' => 'recharge',
            'payment_method' => $data['payment_method'],
            'amount' => $data['amount'],
            'balance_before' => $oldBalance,
            'balance_after' => $newBalance,
            'notes' => $data['notes'] ?? '',
            'is_confirmed' => true,
        ]);

        $wallet->update(['balance' => $newBalance]);
        return $transaction;
    }
}
