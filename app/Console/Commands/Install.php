<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installing application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Artisan::call('key:generate');
        Artisan::call('migrate:fresh');
        Artisan::call('exceptions:install');
        shell_exec('Yes | php artisan shield:install --fresh');
        $user = User::updateOrCreate([
            'email' => 'rupadanawayan@gmail.com',
        ], [
            'name' => 'Rupadana',
            'password' => '12345678',
        ]);

        $panel_user = Role::updateOrCreate([
            'name' => 'panel_user',
            'guard_name' => 'web',
        ]);
        $superAdminRole = Role::find(1);

        $user->roles()->attach($superAdminRole);
        $user->roles()->attach($panel_user);

        $this->info('Successfully Installed');
    }
}
