<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Auth\Access\HandlesAuthorization;

class WalletPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_wallet');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Wallet $wallet): bool
    {
        return $user->can('view_wallet');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_wallet');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Wallet $wallet): bool
    {
        return $user->can('update_wallet');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Wallet $wallet): bool
    {
        return $user->can('delete_wallet');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_wallet');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Wallet $wallet): bool
    {
        return $user->can('force_delete_wallet');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_wallet');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Wallet $wallet): bool
    {
        return $user->can('restore_wallet');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_wallet');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Wallet $wallet): bool
    {
        return $user->can('replicate_wallet');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_wallet');
    }

    /**
     * Determine whether the user can submit the wallet for validation.
     * Only the shop owner can submit their own wallet.
     */
    public function submit(User $user, Wallet $wallet): bool
    {
        // L'utilisateur doit être membre du commerce propriétaire du portefeuille
        if ($wallet->shop->members()->where('users.id', $user->id)->doesntExist()) {
            return false;
        }
        
        // Le portefeuille doit être en statut 'draft' pour pouvoir être soumis
        return $wallet->canBeSubmitted();
    }

    /**
     * Determine whether the user can approve the wallet.
     * Only admins can approve wallets.
     */
    public function approve(User $user, Wallet $wallet): bool
    {
        return $user->is_admin && $wallet->canBeValidated();
    }

    /**
     * Determine whether the user can reject the wallet.
     * Only admins can reject wallets.
     */
    public function reject(User $user, Wallet $wallet): bool
    {
        return $user->is_admin && $wallet->canBeValidated();
    }

    /**
     * Determine whether the user can validate (approve/reject) any wallet.
     */
    public function validateAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }
}
