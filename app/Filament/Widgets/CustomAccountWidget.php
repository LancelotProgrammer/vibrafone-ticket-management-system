<?php

namespace Filament\Widgets;

class CustomAccountWidget extends Widget
{
    protected static ?int $sort = -3;

    protected static string $view = 'filament-panels::widgets.account-widget';

    public static function canView(): bool
    {
        return auth()->user()->roles()->count() == 0;
    }
}
