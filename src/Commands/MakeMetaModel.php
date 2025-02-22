<?php

namespace AugustPermana\MetaGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Command to generate metadata system for a specified model.
 */
class MakeMetaModel extends Command
{
    // Command signature and description
    protected $signature = 'make:metadata {--model= : The model to create metadata for}';
    protected $description = 'Create metadata system for existing model';

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

        // Define the meta model name
        $metaModelName = $modelName . 'Meta';

        // Generate the meta model, update the original model, and create migration
        $this->createMetaModel($metaModelName, $modelName);
        $this->updateOriginalModel($modelName);
        $this->createMigration($modelName);
        
        // Display success messages
        $this->info("Metadata system for {$modelName} created successfully!");
        $this->comment("Run 'php artisan migrate' to create the meta table!");
    }

    /**
     * Create the meta model file.
     *
     * @param string $metaModelName Name of the meta model (e.g., BookMeta)
     * @param string $modelName Name of the original model (e.g., Book)
     */
    protected function createMetaModel($metaModelName, $modelName)
    {
        // Get the stub template for the meta model
        $stub = $this->getMetaModelStub();
        $namespace = 'App\\Models';
        
        // Replace placeholders in the stub with actual values
        $content = str_replace(
            ['{{namespace}}', '{{class}}', '{{parentModel}}', '{{foreignKey}}', '{{tableName}}'],
            [$namespace, $metaModelName, $modelName, Str::snake($modelName) . '_id', Str::snake($modelName) . '_meta'],
            $stub
        );

        // Define the file path for the new meta model
        $path = app_path("Models/{$metaModelName}.php");
        
        // Check if the file already exists
        if (File::exists($path)) {
            $this->error("{$metaModelName} already exists!");
            return;
        }

        // Write the content to the file
        File::put($path, $content);
    }

    /**
     * Get the stub template for the meta model.
     *
     * @return string The stub content
     */
    protected function getMetaModelStub()
    {
        return <<<EOT
<?php

namespace {{namespace}};

use AugustPermana\MetaGenerator\Models\MetaModel;

/**
 * Model for storing metadata related to {{parentModel}}.
 */
class {{class}} extends MetaModel
{
    // Define the table name for this meta model
    protected \$table = '{{tableName}}';
    
    /**
     * Define the relationship to the parent model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return \$this->belongsTo({{parentModel}}::class, '{{foreignKey}}');
    }
}
EOT;
    }

    /**
     * Update the original model to include the HasMetadata trait.
     *
     * @param string $modelName Name of the original model
     */
    protected function updateOriginalModel($modelName)
    {
        // Define the path to the original model file
        $path = app_path("Models/{$modelName}.php");
        if (!File::exists($path)) {
            $this->error("Model {$modelName} not found!");
            return;
        }

        // Read the existing content of the model file
        $content = File::get($path);
        
        // Check if the trait is already included
        if (!Str::contains($content, 'function getMeta(')) {
            // Add the HasMetadata trait to the model
$trait = "\n    use \\AugustPermana\\MetaGenerator\\Traits\\HasMetadata;\n";
            $content = str_replace('class ' . $modelName, 'class ' . $modelName . $trait, $content);
            File::put($path, $content);
        }
    }

    /**
     * Create the migration file for the meta table.
     *
     * @param string $modelName Name of the original model
     */
    protected function createMigration($modelName)
    {
        // Define the table name and migration file details
        $tableName = Str::snake($modelName) . '_meta';
        $timestamp = date('Y_m_d_His');
        $migrationName = "create_{$tableName}_table";
        $path = database_path("migrations/{$timestamp}_{$migrationName}.php");

        // Get the migration stub and replace placeholders
        $stub = $this->getMigrationStub();
        $content = str_replace(
            ['{{tableName}}', '{{foreignKey}}'],
            [$tableName, Str::snake($modelName) . '_id'],
            $stub
        );

        // Check if the migration file already exists
        if (File::exists($path)) {
            $this->error("Migration for {$tableName} already exists!");
            return;
        }

        // Write the migration content to the file
        File::put($path, $content);
    }

    /**
     * Get the stub template for the migration.
     *
     * @return string The stub content
     */
    protected function getMigrationStub()
    {
        return <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to create the {{tableName}} table for metadata.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('{{tableName}}', function (Blueprint \$table) {
            \$table->id();
            \$table->bigInteger('{{foreignKey}}')->unsigned()->index(); // Reference to parent model ID with index
            \$table->string('key'); // Metadata key
            \$table->string('type')->default('string'); // Metadata value type
            \$table->longText('value')->nullable(); // Metadata value
            \$table->timestamps(); // Created and updated timestamps
            
            \$table->index(['{{foreignKey}}', 'key']); // Composite index for performance
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('{{tableName}}');
    }
};
EOT;
    }
}
