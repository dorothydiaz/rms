<?php

namespace App\Database;

use Illuminate\Database\Eloquent\Builder;

class EnterpriseEntityBuilder extends Builder
{
    public function translateColumn($column)
    {
        if (!is_string($column) || $column === '*' || str_contains($column, '(') || str_contains($column, ' as ') || str_contains($column, ' AS ')) {
            return $column;
        }

        if (!method_exists($this->model, 'getEntityPrefix')) {
            return $column;
        }

        $table = $this->model->getTable();
        if (!str_starts_with($table, 'hr_')) {
            return $column;
        }

        $prefix = $this->model->getEntityPrefix();
        if (empty($prefix)) {
            return $column;
        }

        if ($column === 'id') {
            return $this->model->getKeyName();
        }

        if (str_contains($column, '.')) {
            [$t, $c] = explode('.', $column, 2);
            if ($t === $table && !str_starts_with($c, $prefix . '_') && $c !== '*') {
                if ($c === 'id') {
                    return $table . '.' . $this->model->getKeyName();
                }
                return $table . '.' . $prefix . '_' . $c;
            }
            return $column;
        }

        if (!str_starts_with($column, $prefix . '_')) {
            return $prefix . '_' . $column;
        }

        return $column;
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (is_array($column)) {
            $translated = [];
            foreach ($column as $k => $v) {
                if ($this->model instanceof \App\Models\Hr\Employee && ($k === 'employee_id' || $k === 'hr_employees.employee_id')) {
                    if (is_string($v) && !ctype_digit($v) && !is_numeric($v)) {
                        $k = $k === 'hr_employees.employee_id' ? 'hr_employees.employee_code' : 'employee_code';
                    }
                }
                if (is_numeric($k)) {
                    $translated[$k] = $v;
                } else {
                    $translated[$this->translateColumn($k)] = $v;
                }
            }
            return parent::where($translated, $operator, $value, $boolean);
        }

        if (is_string($column)) {
            if ($this->model instanceof \App\Models\Hr\Employee && ($column === 'employee_id' || $column === 'hr_employees.employee_id')) {
                $val = $value ?? $operator;
                if ((is_string($val) && !ctype_digit($val) && !is_numeric($val)) || $operator === 'like') {
                    $column = $column === 'hr_employees.employee_id' ? 'hr_employees.employee_code' : 'employee_code';
                }
            }
            $column = $this->translateColumn($column);
        }
        return parent::where($column, $operator, $value, $boolean);
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'or');
    }

    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        if (is_string($column)) {
            $column = $this->translateColumn($column);
        }
        return parent::whereIn($column, $values, $boolean, $not);
    }

    public function orWhereIn($column, $values)
    {
        return $this->whereIn($column, $values, 'or');
    }

    public function whereNotIn($column, $values, $boolean = 'and')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    public function orWhereNotIn($column, $values)
    {
        return $this->whereNotIn($column, $values, 'or');
    }

    public function whereNull($columns, $boolean = 'and', $not = false)
    {
        if (is_string($columns)) {
            $columns = $this->translateColumn($columns);
        }
        return parent::whereNull($columns, $boolean, $not);
    }

    public function orWhereNull($columns)
    {
        return $this->whereNull($columns, 'or');
    }

    public function whereNotNull($columns, $boolean = 'and')
    {
        return $this->whereNull($columns, $boolean, true);
    }

    public function orWhereNotNull($columns)
    {
        return $this->whereNotNull($columns, 'or');
    }

    public function whereBetween($column, iterable $values, $boolean = 'and', $not = false)
    {
        if (is_string($column)) {
            $column = $this->translateColumn($column);
        }
        return parent::whereBetween($column, $values, $boolean, $not);
    }

    public function orWhereBetween($column, iterable $values)
    {
        return $this->whereBetween($column, $values, 'or');
    }

    public function whereNotBetween($column, iterable $values, $boolean = 'and')
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    public function orWhereNotBetween($column, iterable $values)
    {
        return $this->whereBetween($column, $values, 'or', true);
    }

    public function whereDate($column, $operator, $value = null, $boolean = 'and')
    {
        if (is_string($column)) {
            $column = $this->translateColumn($column);
        }
        return parent::whereDate($column, $operator, $value, $boolean);
    }

    public function orWhereDate($column, $operator, $value = null)
    {
        return $this->whereDate($column, $operator, $value, 'or');
    }

    public function whereTime($column, $operator, $value = null, $boolean = 'and')
    {
        if (is_string($column)) {
            $column = $this->translateColumn($column);
        }
        return parent::whereTime($column, $operator, $value, $boolean);
    }

    public function orWhereTime($column, $operator, $value = null)
    {
        return $this->whereTime($column, $operator, $value, 'or');
    }

    public function whereDay($column, $operator, $value = null, $boolean = 'and')
    {
        if (is_string($column)) {
            $column = $this->translateColumn($column);
        }
        return parent::whereDay($column, $operator, $value, $boolean);
    }

    public function orWhereDay($column, $operator, $value = null)
    {
        return $this->whereDay($column, $operator, $value, 'or');
    }

    public function whereMonth($column, $operator, $value = null, $boolean = 'and')
    {
        if (is_string($column)) {
            $column = $this->translateColumn($column);
        }
        return parent::whereMonth($column, $operator, $value, $boolean);
    }

    public function orWhereMonth($column, $operator, $value = null)
    {
        return $this->whereMonth($column, $operator, $value, 'or');
    }

    public function whereYear($column, $operator, $value = null, $boolean = 'and')
    {
        if (is_string($column)) {
            $column = $this->translateColumn($column);
        }
        return parent::whereYear($column, $operator, $value, $boolean);
    }

    public function orWhereYear($column, $operator, $value = null)
    {
        return $this->whereYear($column, $operator, $value, 'or');
    }

    public function whereJsonContains($column, $value, $boolean = 'and', $not = false)
    {
        if (is_string($column)) {
            $column = $this->translateColumn($column);
        }
        return parent::whereJsonContains($column, $value, $boolean, $not);
    }

    public function orWhereJsonContains($column, $value)
    {
        return $this->whereJsonContains($column, $value, 'or');
    }

    public function whereJsonDoesntContain($column, $value, $boolean = 'and')
    {
        return $this->whereJsonContains($column, $value, $boolean, true);
    }

    public function orWhereJsonDoesntContain($column, $value)
    {
        return $this->whereJsonDoesntContain($column, $value, 'or');
    }

    public function groupBy(...$groups)
    {
        $translated = [];
        foreach ($groups as $group) {
            if (is_array($group)) {
                $translated[] = array_map(fn($col) => is_string($col) ? $this->translateColumn($col) : $col, $group);
            } elseif (is_string($group)) {
                $translated[] = $this->translateColumn($group);
            } else {
                $translated[] = $group;
            }
        }
        return parent::groupBy(...$translated);
    }

    public function having($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (is_string($column)) {
            $column = $this->translateColumn($column);
        }
        return parent::having($column, $operator, $value, $boolean);
    }

    public function orderBy($column, $direction = 'asc')
    {
        if (is_string($column)) {
            $column = $this->translateColumn($column);
        }
        return parent::orderBy($column, $direction);
    }

    public function latest($column = null)
    {
        if (is_null($column)) {
            $column = $this->model->getCreatedAtColumn() ?? 'created_at';
        }
        return $this->orderBy($column, 'desc');
    }

    public function oldest($column = null)
    {
        if (is_null($column)) {
            $column = $this->model->getCreatedAtColumn() ?? 'created_at';
        }
        return $this->orderBy($column, 'asc');
    }

    public function pluck($column, $key = null)
    {
        if (is_string($column)) {
            $column = $this->translateColumn($column);
        }
        if (is_string($key)) {
            $key = $this->translateColumn($key);
        }
        return parent::pluck($column, $key);
    }

    public function get($columns = ['*'])
    {
        if (is_array($columns) && $columns !== ['*']) {
            $columns = array_map(fn($c) => is_string($c) ? $this->translateColumn($c) : $c, $columns);
        }
        return parent::get($columns);
    }

    public function first($columns = ['*'])
    {
        if (is_array($columns) && $columns !== ['*']) {
            $columns = array_map(fn($c) => is_string($c) ? $this->translateColumn($c) : $c, $columns);
        }
        return parent::first($columns);
    }

    public function find($id, $columns = ['*'])
    {
        if (is_array($columns) && $columns !== ['*']) {
            $columns = array_map(fn($c) => is_string($c) ? $this->translateColumn($c) : $c, $columns);
        }
        return parent::find($id, $columns);
    }

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
    {
        if (is_array($columns) && $columns !== ['*']) {
            $columns = array_map(fn($c) => is_string($c) ? $this->translateColumn($c) : $c, $columns);
        }
        return parent::paginate($perPage, $columns, $pageName, $page, $total);
    }

    public function select($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $translated = [];
        foreach ($columns as $c) {
            $translated[] = is_string($c) ? $this->translateColumn($c) : $c;
        }
        return parent::select($translated);
    }

    public function distinct($column = false)
    {
        if (is_string($column)) {
            $column = $this->translateColumn($column);
        } elseif (is_array($column)) {
            $column = array_map([$this, 'translateColumn'], $column);
        }
        $this->query->distinct($column);
        return $this;
    }

    public function sum($column)
    {
        return $this->query->sum($this->translateColumn($column));
    }

    public function avg($column)
    {
        return $this->query->avg($this->translateColumn($column));
    }

    public function average($column)
    {
        return $this->avg($column);
    }

    public function min($column)
    {
        return $this->query->min($this->translateColumn($column));
    }

    public function max($column)
    {
        return $this->query->max($this->translateColumn($column));
    }

    public function count($columns = '*')
    {
        return $this->query->count($this->translateColumn($columns));
    }

    public function update(array $values)
    {
        $translated = [];
        foreach ($values as $k => $v) {
            $translated[$this->translateColumn($k)] = $v;
        }
        return parent::update($translated);
    }

    public function insert(array $values)
    {
        if (empty($values)) {
            return parent::insert($values);
        }

        $translated = [];
        if (is_array(reset($values))) {
            foreach ($values as $row) {
                $trRow = [];
                foreach ($row as $k => $v) {
                    $trRow[$this->translateColumn($k)] = $v;
                }
                $translated[] = $trRow;
            }
        } else {
            foreach ($values as $k => $v) {
                $translated[$this->translateColumn($k)] = $v;
            }
        }
        return parent::insert($translated);
    }

    public function aggregate($function, $columns = ['*'])
    {
        $columns = (array) $columns;
        $translated = [];
        foreach ($columns as $c) {
            $translated[] = is_string($c) ? $this->translateColumn($c) : $c;
        }
        return parent::aggregate($function, $translated);
    }
}
