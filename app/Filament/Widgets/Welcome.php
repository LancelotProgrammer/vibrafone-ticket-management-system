<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class Welcome extends Widget
{
    protected static ?int $sort = -3;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.welcome';

    public static function canView(): bool
    {
        return auth()->user()->roles()->count() == 0;
    }
}
