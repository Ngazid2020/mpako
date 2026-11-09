<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Shop;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class AdminStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalShops = Shop::query()->count();
        $activeShops = Shop::query()->where('is_active', true)->count();
        $inactiveShops = Shop::query()->where('is_active', false)->count();

        $totalUsers = User::query()->count();

        $newShopsToday = Shop::query()
            ->whereDate('created_at', Carbon::today())
            ->count();

        $newShopsThisWeek = Shop::query()
            ->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ])
            ->count();

        return [
            Stat::make('Commerces', number_format($totalShops))
                ->description('Nombre total de commerces')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('primary'),

            Stat::make('Utilisateurs', number_format($totalUsers))
                ->description('Nombre total d’utilisateurs')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Commerces actifs', number_format($activeShops))
                ->description('Commerces actuellement actifs')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Commerces inactifs', number_format($inactiveShops))
                ->description('Commerces désactivés')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Nouveaux commerces aujourd’hui', number_format($newShopsToday))
                ->description('Inscriptions du jour')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning'),

            Stat::make('Nouveaux commerces cette semaine', number_format($newShopsThisWeek))
                ->description('Inscriptions de la semaine')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}