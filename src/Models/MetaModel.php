<?php

namespace AugustPermana\MetaGenerator\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model for metadata tables.
 */
class MetaModel extends Model
{
    // Define fillable fields for mass assignment
    protected $fillable = ['parent_id', 'key', 'type', 'value'];

    /**
     * Define a polymorphic relationship to the parent model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function parent()
    {
        return $this->morphTo('parent');
    }

    /**
     * Cast a stored value to its original type using the trait's logic.
     *
     * @param string|null $value The stored value
     * @param string $type The type to cast to
     * @return mixed The casted value
     */
    public function castValue($value, $type)
    {
return \AugustPermana\MetaGenerator\Traits\HasMetadataHelper::castValue($value, $type);
    }
}
