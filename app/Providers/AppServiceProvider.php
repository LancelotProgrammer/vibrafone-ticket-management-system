<?php

namespace App\Providers;

use Error;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // DB::listen(function ($query) {
        //     if (strpos(strtolower($query->sql), 'select') === 0) {
        //         Log::channel('sqlselects')->info(
        //             $query->sql,
        //             [
        //                 'bindings' => $query->bindings,
        //                 'time' => $query->time,
        //                 'trace' => "\n" . implode("\n", $this->getFilesFromBacktrace()),
        //             ]
        //         );
        //     } elseif (strpos(strtolower($query->sql), 'update `sessions`') === 0) {
        //         return;
        //     } else {
        //         Log::channel('sqlactions')->info(
        //             $query->sql,
        //             [
        //                 'bindings' => $query->bindings,
        //                 'time' => $query->time,
        //                 'trace' => "\n" . implode("\n", $this->getFilesFromBacktrace()),
        //             ]
        //         );
        //     }
        // });
    }

    function getFilesFromBacktrace()
    {
        try {
            $files = [];
            $backtrace = debug_backtrace();
            foreach ($backtrace as $key => $value) {
                if (strpos($value['file'], 'vendor') === false) {
                    $files[] = $value['file'] . ':' . $value['line'];
                } else {
                    continue;
                }
            }
            return $files;
        } catch (Exception|Throwable|Error) {
            return [];
        }
    }
}
