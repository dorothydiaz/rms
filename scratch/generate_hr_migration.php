<?php

$renames = json_decode(file_get_contents('scratch/proposed_hr_column_renames.json'), true);

$export = var_export($renames, true);

$migrationContent = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Map of HR tables and entity-prefixed column renames.
     */
    protected array \$renames = $export;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach (\$this->renames as \$tableName => \$columns) {
                if (!Schema::hasTable(\$tableName)) {
                    continue;
                }

                Schema::table(\$tableName, function (Blueprint \$table) use (\$tableName, \$columns) {
                    foreach (\$columns as \$oldCol => \$newCol) {
                        if (Schema::hasColumn(\$tableName, \$oldCol) && !Schema::hasColumn(\$tableName, \$newCol)) {
                            \$table->renameColumn(\$oldCol, \$newCol);
                        }
                    }
                });
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach (\$this->renames as \$tableName => \$columns) {
                if (!Schema::hasTable(\$tableName)) {
                    continue;
                }

                Schema::table(\$tableName, function (Blueprint \$table) use (\$tableName, \$columns) {
                    foreach (array_reverse(\$columns) as \$oldCol => \$newCol) {
                        if (Schema::hasColumn(\$tableName, \$newCol) && !Schema::hasColumn(\$tableName, \$oldCol)) {
                            \$table->renameColumn(\$newCol, \$oldCol);
                        }
                    }
                });
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
};

PHP;

file_put_contents('database/migrations/2026_10_11_210001_rename_hr_columns_to_entity_prefixed_schema.php', $migrationContent);
echo "Successfully generated database/migrations/2026_10_11_210001_rename_hr_columns_to_entity_prefixed_schema.php\n";
