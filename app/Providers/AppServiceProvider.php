<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        // パスワードのデフォルトセキュリティポリシー設定 (8文字以上・英大文字/小文字/数字必須)
        Password::defaults(function () {
            $rule = Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers();

            return $this->app->isProduction()
                ? $rule->uncompromised()
                : $rule;
        });
    }
}
