<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;

class TestDatabaseConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:test';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Test database connection and configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing database connection...');
        $this->newLine();

        try {
            // Get connection details
            $connection = config('database.default');
            $config = config("database.connections.{$connection}");

            $this->info('Connection Configuration:');
            $this->table(
                ['Key', 'Value'],
                [
                    ['Driver', $config['driver'] ?? 'N/A'],
                    ['Host', $config['host'] ?? 'N/A'],
                    ['Port', $config['port'] ?? 'N/A'],
                    ['Database', $config['database'] ?? 'N/A'],
                    ['Username', $config['username'] ?? 'N/A'],
                    ['SSL Mode', $config['sslmode'] ?? $config['ssl'] ?? 'N/A'],
                ]
            );

            $this->newLine();
            $this->info('Attempting connection...');

            // Test connection
            DB::connection($connection)->getPdo();

            $this->info('✓ Successfully connected to database!');
            $this->newLine();

            // Try a test query
            $this->info('Testing a simple query...');
            $result = DB::select('SELECT NOW() as current_time');
            $this->table(['Result'], [['Current DB Time: ' . $result[0]->current_time]]);

            $this->info('✓ Query executed successfully!');

            return 0;
        } catch (Exception $e) {
            $this->error('✗ Database connection failed!');
            $this->newLine();
            $this->error('Error Message:');
            $this->error($e->getMessage());
            $this->newLine();

            $this->warn('Troubleshooting Steps:');
            $this->line('1. Verify DB_HOST is correct and accessible');
            $this->line('2. Verify DB_USERNAME and DB_PASSWORD are correct');
            $this->line('3. Check if the database server is running');
            $this->line('4. If using Supabase pooler, ensure DB_PERSISTENT=false');
            $this->line('5. Check if SSL mode is correctly configured');

            return 1;
        }
    }
}
