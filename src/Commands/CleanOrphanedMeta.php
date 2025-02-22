<?php

namespace AugustPermana\MetaGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Command to clean orphaned metadata records.
 */
class CleanOrphanedMeta extends Command
{
    // Command signature and description
    protected $signature = 'metadata:clean-orphaned {--model= : The model to clean orphaned metadata for}';
    protected $description = 'Clean orphaned metadata records where the parent record no longer exists';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the model name from the command option and ensure it's in StudlyCase
        $modelName = Str::studly($this->option('model'));
        if (!$modelName) {
            $this->error('Please specify a model using --model option');
            return;
        }

        // Define model class, meta table, foreign key, and parent table names
        $modelClass = "App\\Models\\{$modelName}";
        $metaTable = Str::snake($modelName) . '_meta';
        $foreignKey = Str::snake($modelName) . '_id';
        $parentTable = Str::snake(Str::plural($modelName));

        // Check if the model class exists
        if (!class_exists($modelClass)) {
            $this->error("Model {$modelName} not found!");
            return;
        }

        // Check if the meta table exists
        if (!Schema::hasTable($metaTable)) {
            $this->error("Meta table {$metaTable} does not exist!");
            return;
        }

        // Check if the parent table exists
        if (!Schema::hasTable($parentTable)) {
            $this->error("Parent table {$parentTable} does not exist!");
            return;
        }

        // Display warning about data deletion and backup recommendation
        $this->warn('WARNING: This command will delete orphaned metadata records from the database.');
        $this->warn('It is strongly recommended to backup your database before proceeding.');

        // Ask for user confirmation, default to "No"
        if (!$this->confirm('Do you want to continue? [Yes/No]', false)) {
            $this->info('Operation cancelled by user.');
            return;
        }

        // Delete orphaned records where foreign key does not exist in parent table
        $deleted = DB::table($metaTable)
            ->whereNotExists(function ($query) use ($parentTable, $foreignKey, $metaTable) {
                $query->select(DB::raw(1))
                      ->from($parentTable)
                      ->whereColumn("{$parentTable}.id", "{$metaTable}.{$foreignKey}");
            })
            ->delete();

        // Display the result of the operation
        $this->info("Deleted {$deleted} orphaned records from {$metaTable}.");
    }
}
