<?php

namespace App\Providers;

use App\Enums\Peran;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Dipakai rute (middleware can:...) dan menu di navbar (@can).
        Gate::define('admin', fn (User $user) => $user->adalahAdmin());
        Gate::define('booking', fn (User $user) => $user->role === Peran::User);
    }
}
