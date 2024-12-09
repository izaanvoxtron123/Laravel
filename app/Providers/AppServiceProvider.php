<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Enforce HTTPS URLs
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }
        
        Relation::enforceMorphMap([
            'article' => 'App\Models\Article',
            'page' => 'App\Models\Page',
            'user' => 'App\Models\User',
            'disease' => 'App\Models\Disease',
            'drug' => 'App\Models\Drug',
            'topic' => 'App\Models\Topics',
            'chat' => 'App\Models\Chat',
        ]);
    }
}
