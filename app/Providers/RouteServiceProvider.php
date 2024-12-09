<?php

namespace App\Providers;

use App\Models\{Article, DoctorClinic, Page};
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/admin/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
        Route::bind('page', function ($value) {
            return Page::where('slug', $value)->where('reference_type', 'page')->firstOrFail();
        });
        Route::bind('article', function ($value) {
            return Article::where('slug', $value)->firstOrFail();
        });
        Route::bind('topic', function ($value, $route) {
            if($route->parameter('subTopic')){
                return $value;
            }
            return Page::where('slug', $value)->where('reference_type', 'topic')->firstOrFail();
        });
        Route::bind('subTopic', function ($value, $route) {
            $topic = $route->parameter('topic');
            if($route->parameter('nestedTopic')){
                return $topic . '/' .$value;
            }
            return Page::where('slug', $topic."/$value")->where('reference_type', 'topic')->firstOrFail();
        });
        Route::bind('nestedTopic', function ($value, $route) {
            $subTopic = $route->parameter('subTopic');
            return Page::where('slug', $subTopic.'/'.$value)->where('reference_type', 'topic')->firstOrFail();
        });
        Route::bind('disease', function ($value) {
            return Page::where('slug', $value)->where('reference_type', 'disease')->firstOrFail();
        });
        Route::bind('drug', function ($value) {
            return Page::where('slug', $value)->where('reference_type', 'drug')->firstOrFail();
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
