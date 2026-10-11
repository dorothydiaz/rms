<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Hr\Employee;
use Illuminate\Database\Eloquent\Builder;

class EntityBuilder extends Builder
{
    protected function translateColumn($column)
    {
        if (!is_string($column)) return $column;
        $table = $this->model->getTable();
        $prefix = rtrim(str_replace('hr_', '', $table), 's');
        if (str_ends_with($prefix, 'ie')) $prefix = substr($prefix, 0, -2) . 'y'; // companies -> company

        // If already prefixed or dotted, return
        if (str_contains($column, '.') || str_starts_with($column, $prefix . '_')) {
            return $column;
        }

        $prefixed = $prefix . '_' . $column;
        return $prefixed;
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (is_string($column)) {
            $column = $this->translateColumn($column);
        }
        return parent::where($column, $operator, $value, $boolean);
    }
}

$builder = new EntityBuilder(Employee::query()->getQuery());
$builder->setModel(new Employee);

$sql = $builder->where('employment_status', 'Active')->toSql();
echo "TRANSLATED SQL: " . $sql . "\n";
