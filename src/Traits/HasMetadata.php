<?php

namespace August\MetaGenerator\Traits;

use Carbon\Carbon;

/**
 * Trait to provide metadata functionality to models.
 */
trait HasMetadata
{
    /**
     * Define the hasMany relationship to the meta model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function meta()
    {
        return $this->hasMany($this->getMetaModelClass(), $this->getForeignKeyName());
    }

    /**
     * Get a single meta value by key, with optional default value and type casting.
     *
     * @param string $key The meta key to retrieve
     * @param mixed $default Default value if meta is not found (optional)
     * @param string|null $type Type to cast the default value (optional)
     * @return mixed The meta value or default value
     */
    public function getMeta($key, $default = null, $type = null)
    {
        // Fetch the meta record by key
        $meta = $this->meta()->where('key', $key)->first();
        
        // Return the casted value if meta exists
        if ($meta) {
            return $this->castValue($meta->value, $meta->type);
        }
        
        // Handle default value if provided
        if (!is_null($default)) {
            // Use specified type or detect automatically
            $castType = $type ?? $this->detectType($default);
            return $this->castValue($this->serializeValue($default, $castType), $castType);
        }
        
        // Return null if no meta and no default
        return null;
    }

    /**
     * Set a single meta value, update if key exists.
     *
     * @param string $key The meta key
     * @param mixed $value The value to set
     * @return \Illuminate\Database\Eloquent\Model The meta model instance
     */
    public function setMeta($key, $value)
    {
        $type = $this->detectType($value);
        
        // Update or create the meta record
        return $this->meta()->updateOrCreate(
            ['key' => $key],
            [
                'type' => $type,
                'value' => $this->serializeValue($value, $type)
            ]
        );
    }

    /**
     * Sync meta values by replacing all existing with a new set.
     *
     * @param array $metaData Associative array of key-value pairs
     * @return bool Whether the insert was successful
     */
    public function syncMeta(array $metaData)
    {
        // Delete all existing meta records
        $this->meta()->delete();
        
        // Prepare new records for batch insert
        $records = [];
        foreach ($metaData as $key => $value) {
            $type = $this->detectType($value);
            $records[] = [
                $this->getForeignKeyName() => $this->id,
                'key' => $key,
                'type' => $type,
                'value' => $this->serializeValue($value, $type),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        
        // Insert all new records at once
        return $this->meta()->insert($records);
    }

    /**
     * Set multiple meta values at once, updating existing keys.
     *
     * @param array $metaData Associative array of key-value pairs
     * @return $this The current model instance
     */
    public function setManyMeta(array $metaData)
    {
        foreach ($metaData as $key => $value) {
            $type = $this->detectType($value);
            $this->meta()->updateOrCreate(
                ['key' => $key],
                [
                    'type' => $type,
                    'value' => $this->serializeValue($value, $type)
                ]
            );
        }
        return $this;
    }

    /**
     * Check if a meta key exists.
     *
     * @param string $key The meta key to check
     * @return bool Whether the key exists
     */
    public function hasMeta($key)
    {
        return $this->meta()->where('key', $key)->exists();
    }

    /**
     * Remove a single meta by key.
     *
     * @param string $key The meta key to remove
     * @return int The number of records deleted
     */
    public function removeMeta($key)
    {
        return $this->meta()->where('key', $key)->delete();
    }

    /**
     * Get the fully qualified class name of the meta model.
     *
     * @return string The meta model class name
     */
    protected function getMetaModelClass()
    {
        return 'App\\Models\\' . class_basename($this) . 'Meta';
    }

    /**
     * Get the foreign key name based on the model name.
     *
     * @return string The foreign key name (e.g., book_id)
     */
    protected function getForeignKeyName()
    {
        return Str::snake(class_basename($this)) . '_id';
    }

    /**
     * Detect the type of a given value.
     *
     * @param mixed $value The value to analyze
     * @return string The detected type
     */
    protected function detectType($value)
    {
        if (is_null($value)) return 'string';
        if (is_bool($value)) return 'boolean';
        if (is_int($value)) return 'integer';
        if (is_float($value)) return 'float';
        if (is_double($value)) return 'double';
        if (is_array($value) || is_object($value)) return 'json';
        if ($value instanceof \DateTime || $value instanceof Carbon) return 'datetime';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$value)) return 'date';
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', (string)$value)) return 'time';
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string)$value)) return 'timestamp';
        if (is_string($value) && strlen($value) > 65535) return 'longtext';
        if (is_string($value) && strlen($value) > 255) return 'text';
        if (is_resource($value) || (is_string($value) && !mb_check_encoding($value, 'UTF-8'))) return 'binary';
        if (is_numeric($value) && strpos((string)$value, '.') !== false) return 'decimal';
        return 'string';
    }

    /**
     * Serialize a value based on its type for storage.
     *
     * @param mixed $value The value to serialize
     * @param string $type The type of the value
     * @return string|null The serialized value
     */
    protected function serializeValue($value, $type)
    {
        if (is_null($value)) return null;
        
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'json':
                return json_encode($value);
            case 'datetime':
            case 'timestamp':
                return $value instanceof Carbon ? $value->toDateTimeString() : (string)$value;
            case 'date':
                return $value instanceof Carbon ? $value->toDateString() : (string)$value;
            case 'binary':
                return base64_encode($value instanceof \Resource ? stream_get_contents($value) : $value);
            default:
                return (string)$value;
        } // end switch
    } // end serializeValue

    /**
     * Scope a query to include models having a given meta key and optionally a value.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $key
     * @param mixed $value (optional)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereHasMeta($query, $key, $value = null)
    {
        return $query->whereHas('meta', function ($q) use ($key, $value) {
            $q->where('key', $key);
            if (!is_null($value)) {
                $q->where('value', $value);
            }
        });
    }
    
    /**
     * Cast a stored value to its original type.
     *
     * @param string|null $value The stored value
     * @param string $type The type to cast to
     * @return mixed The casted value
     */
    protected function castValue($value, $type)
    {
        if (is_null($value)) return null;

        switch ($type) {
            case 'boolean':
                return (bool)$value;
            case 'integer':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'double':
                return (double)$value;
            case 'decimal':
                return (float)$value;
            case 'json':
                return json_decode($value, true);
            case 'datetime':
            case 'timestamp':
                return Carbon::parse($value);
            case 'date':
                return Carbon::parse($value)->startOfDay();
            case 'time':
                return Carbon::parse($value)->toTimeString();
            case 'binary':
                return base64_decode($value);
            case 'longtext':
            case 'text':
            case 'string':
                return (string)$value;
            default:
                return $value;
        }
    }
} // end trait
