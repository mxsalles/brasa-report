<?php

namespace App\Providers;

use App\Models\AreaMonitorada;
use App\Models\DeteccaoSatelite;
use App\Models\Incendio;
use App\Models\LeituraMeteorologica;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Database\Seeders\AreaMonitoradaSeeder;
use Database\Seeders\UsuarioTesteSeeder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        $runningAutomatedTestSuite = defined('APP_RUNNING_TESTS') && APP_RUNNING_TESTS === true;

        if (! $runningAutomatedTestSuite && ! $this->app->runningUnitTests() && ! $this->app->environment('testing')) {
            try {
                if (Schema::hasTable('areas_monitoradas') && AreaMonitorada::query()->doesntExist()) {
                    Artisan::call('db:seed', [
                        '--class' => AreaMonitoradaSeeder::class,
                        '--no-interaction' => true,
                    ]);
                }

                if (! app()->isProduction() && Schema::hasTable('usuarios')) {
                    $emailsContasDev = [
                        UsuarioTesteSeeder::EMAIL_TESTE,
                        UsuarioTesteSeeder::EMAIL_GESTOR_TESTE,
                        UsuarioTesteSeeder::EMAIL_ADMINISTRADOR_TESTE,
                    ];
                    $cadastrados = Usuario::query()->whereIn('email', $emailsContasDev)->count();
                    if ($cadastrados < count($emailsContasDev)) {
                        Artisan::call('db:seed', [
                            '--class' => UsuarioTesteSeeder::class,
                            '--no-interaction' => true,
                        ]);
                    }
                }
            } catch (QueryException) {
                //
            }
        }

        Relation::enforceMorphMap([
            'usuarios' => Usuario::class,
            'incendios' => Incendio::class,
            'leituras_meteorologicas' => LeituraMeteorologica::class,
            'deteccoes_satelite' => DeteccaoSatelite::class,
        ]);

        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(8)
            : null
        );
    }
}
