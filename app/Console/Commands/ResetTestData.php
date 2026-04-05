<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetTestData extends Command
{
    protected $signature   = 'test:reset';
    protected $description = 'Wipe all test data and re-seed the admin user (run before Newman)';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('This command cannot run in production.');
            return 1;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('subscription_transactions')->truncate();
        DB::table('subscriptions')->truncate();
        DB::table('plan_prices')->truncate();
        DB::table('plans')->truncate();
        DB::table('personal_access_tokens')->truncate();
        DB::table('users')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        DB::table('users')->insert([
            'name'       => 'admin',
            'email'      => 'admin@admin.com',
            'password'   => bcrypt('admin123'),
            'is_admin'   => 1,
            'balance'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info('Test data reset. Admin user re-seeded.');
        $this->info('You can now run: newman run tests/postman-collection.json --env-var "baseUrl=http://127.0.0.1:8000/api" --delay-request 300 --verbose');

        return 0;
    }
}
