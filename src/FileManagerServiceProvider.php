<?phpnamespace Tupy\FileManager;use Illuminate\Filesystem\Filesystem;use Illuminate\Support\Collection;use Illuminate\Support\ServiceProvider;class FileManagerServiceProvider extends ServiceProvider{    /**     * Bootstrap the application services.     * @param Filesystem $filesystem     */    public function boot(Filesystem $filesystem)    {        if (isNotLumen()) {            /*             * Optional methods to load your package assets             */            // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'file-manager');            // $this->loadViewsFrom(__DIR__.'/../resources/views', 'file-manager');            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');            // $this->loadRoutesFrom(__DIR__.'/routes.php');        }        if ($this->app->runningInConsole() && isNotLumen()) {            $this->publishes([                __DIR__.'/../config/config.php' => config_path('file-manager.php'),            ], 'file-manager-config');            $this->publishes([                __DIR__.'/../database/migrations/create_file_manager_table.stub' => $this->getMigrationFileName($filesystem)            ], 'file-manager-migrations');            // Publishing the views.            /*$this->publishes([                __DIR__.'/../resources/views' => resource_path('views/vendor/file-manager'),            ], 'views');*/            // Publishing assets.            /*$this->publishes([                __DIR__.'/../resources/assets' => public_path('vendor/file-manager'),            ], 'assets');*/            // Publishing the translation files.            /*$this->publishes([                __DIR__.'/../resources/lang' => resource_path('lang/vendor/file-manager'),            ], 'lang');*/            // Registering package commands.            // $this->commands([]);        }    }    /**     * Register the application services.     */    public function register()    {        if (isNotLumen()) {            // Automatically apply the package configuration            $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'file-manager');        }        // Register the main class to use with the facade        $this->app->singleton('filemanager', function () {            return new FileManager;        });    }    /**     * Returns existing migration file if found, else uses the current timestamp.     *     * @param Filesystem $filesystem     * @return string     */    protected function getMigrationFileName(Filesystem $filesystem): string    {        $timestamp = date('Y_m_d_His');        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)            ->flatMap(function ($path) use ($filesystem) {                return $filesystem->glob($path.'*_create_file_manager_table.php');            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_file_manager_table.php")            ->first();    }}