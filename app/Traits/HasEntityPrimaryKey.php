<?php

namespace App\Traits;

use App\Database\EnterpriseEntityBuilder;

trait HasEntityPrimaryKey
{
    public static function bootHasEntityPrimaryKey()
    {
        static::saving(function ($model) {
            if ($model->getKeyName() !== 'id' && array_key_exists('id', $model->attributes)) {
                unset($model->attributes['id']);
            }

            $prefix = $model->getEntityPrefix();
            if (!empty($prefix)) {
                foreach ($model->attributes as $key => $val) {
                    if ($key === $model->getKeyName() || str_starts_with($key, $prefix . '_')) {
                        continue;
                    }
                    $prefixed = $prefix . '_' . $key;
                    if (!array_key_exists($prefixed, $model->attributes)) {
                        $model->attributes[$prefixed] = $val;
                    }
                    unset($model->attributes[$key]);
                }
            }
        });
    }

    /**
     * Get the entity prefix based on primary key or table name.
     */
    public function getEntityPrefix(): string
    {
        $table = $this->getTable();
        if (!str_starts_with($table, 'hr_')) {
            return '';
        }

        if (property_exists($this, 'primaryKey') && $this->primaryKey && $this->primaryKey !== 'id') {
            return str_replace('_id', '', $this->primaryKey);
        }

        $clean = str_replace('hr_', '', $table);
        $clean = rtrim($clean, 's');
        if (str_ends_with($clean, 'ie')) {
            $clean = substr($clean, 0, -2) . 'y';
        }
        return $clean;
    }

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->getKeyName();
    }

    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
    {
        if (is_null($relation)) {
            $relation = $this->guessBelongsToRelation();
        }

        $instance = $this->newRelatedInstance($related);

        if (is_null($foreignKey)) {
            $foreignKey = \Illuminate\Support\Str::snake($relation) . '_id';
        }

        if (is_null($ownerKey)) {
            $ownerKey = $instance->getKeyName();
        }

        return $this->newBelongsTo(
            $instance->newQuery(), $this, $foreignKey, $ownerKey, $relation
        );
    }

    /**
     * Translate column name to entity-prefixed version.
     */
    public function translateColumnName(string $column): string
    {
        $prefix = $this->getEntityPrefix();
        if ($column === 'id') {
            return $this->getKeyName();
        }
        if (!str_starts_with($column, $prefix . '_')) {
            return $prefix . '_' . $column;
        }
        return $column;
    }

    /**
     * Qualify the given column name by the model's table with entity prefix.
     */
    public function qualifyColumn($column)
    {
        $prefix = $this->getEntityPrefix();
        if (empty($prefix)) {
            return parent::qualifyColumn($column);
        }

        if (str_contains($column, '.')) {
            [$table, $col] = explode('.', $column, 2);
            if ($table === $this->getTable() && !str_starts_with($col, $prefix . '_') && $col !== '*') {
                return $table . '.' . $prefix . '_' . $col;
            }
            return $column;
        }

        if (!str_starts_with($column, $prefix . '_') && $column !== '*') {
            return $this->getTable() . '.' . $prefix . '_' . $column;
        }

        return $this->getTable() . '.' . $column;
    }

    /**
     * Create a new Eloquent query builder for the model.
     */
    public function newEloquentBuilder($query)
    {
        return new EnterpriseEntityBuilder($query);
    }

    public function getCreatedAtColumn()
    {
        $prefix = $this->getEntityPrefix();
        return !empty($prefix) ? $prefix . '_created_at' : parent::getCreatedAtColumn();
    }

    public function getUpdatedAtColumn()
    {
        $prefix = $this->getEntityPrefix();
        return !empty($prefix) ? $prefix . '_updated_at' : parent::getUpdatedAtColumn();
    }

    public function getCasts()
    {
        $casts = parent::getCasts();
        $prefix = $this->getEntityPrefix();
        if (!empty($prefix)) {
            foreach ($casts as $k => $type) {
                if (!str_starts_with($k, $prefix . '_')) {
                    $casts[$prefix . '_' . $k] = $type;
                }
            }
        }
        return $casts;
    }

    /**
     * Dynamically resolve attribute access.
     */
    public function getAttribute($key)
    {
        if ($key === 'id') {
            if ($this instanceof \App\Models\Hr\Employee) {
                return $this->attributes['employee_id'] ?? $this->getKey();
            }
            return parent::getAttribute($this->getKeyName());
        }

        if ($key === 'created_at') {
            return parent::getAttribute($this->getCreatedAtColumn());
        }

        if ($key === 'updated_at') {
            return parent::getAttribute($this->getUpdatedAtColumn());
        }

        $prefix = $this->getEntityPrefix();
        if (empty($prefix)) {
            return parent::getAttribute($key);
        }

        $prefixedKey = $prefix . '_' . $key;
        if (array_key_exists($prefixedKey, $this->attributes)) {
            return $this->getAttributeValue($prefixedKey);
        }

        return parent::getAttribute($key);
    }

    public function isFillable($key)
    {
        if (parent::isFillable($key)) {
            return true;
        }

        $prefix = $this->getEntityPrefix();
        if (!empty($prefix) && str_starts_with($key, $prefix . '_')) {
            $unprefixed = substr($key, strlen($prefix) + 1);
            return parent::isFillable($unprefixed);
        }

        return false;
    }

    /**
     * Dynamically set attribute values.
     */
    public function setAttribute($key, $value)
    {
        if ($key === 'id') {
            return parent::setAttribute($this->getKeyName(), $value);
        }

        $prefix = $this->getEntityPrefix();
        if (empty($prefix)) {
            return parent::setAttribute($key, $value);
        }

        if ($this->hasSetMutator($key)) {
            $method = 'set' . \Illuminate\Support\Str::studly($key) . 'Attribute';
            return $this->{$method}($value);
        }

        if ($key === 'created_at') {
            return parent::setAttribute($this->getCreatedAtColumn(), $value);
        }

        if ($key === 'updated_at') {
            return parent::setAttribute($this->getUpdatedAtColumn(), $value);
        }

        if (!str_starts_with($key, $prefix . '_')) {
            $key = $prefix . '_' . $key;
        }

        return parent::setAttribute($key, $value);
    }

    public function __isset($key)
    {
        if ($key === 'id') {
            return parent::__isset($this->getKeyName());
        }

        $prefix = $this->getEntityPrefix();
        if (empty($prefix)) {
            return parent::__isset($key);
        }

        $prefixedKey = $prefix . '_' . $key;
        if (array_key_exists($prefixedKey, $this->attributes)) {
            return parent::__isset($prefixedKey);
        }

        return parent::__isset($key);
    }

    public function toArray()
    {
        $array = parent::toArray();
        $prefix = $this->getEntityPrefix();
        if (!empty($prefix)) {
            $unprefixed = [];
            foreach ($array as $key => $val) {
                if (str_starts_with($key, $prefix . '_')) {
                    $unprefixedKey = substr($key, strlen($prefix) + 1);
                    if (!array_key_exists($unprefixedKey, $array)) {
                        $unprefixed[$unprefixedKey] = $val;
                    }
                }
            }
            $array = array_merge($array, $unprefixed);
        }
        return $array;
    }
}
