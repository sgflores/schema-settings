<?php

namespace SgFlores\SchemaSetting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Setting Model
 *
 * Eloquent model for the schema_settings database table.
 * Stores setting values with polymorphic relationships to reference models.
 *
 * Database Structure:
 * - id: Primary key
 * - key: Setting identifier
 * - value: JSON-encoded (and possibly encrypted) value
 * - reference_type: Polymorphic type (model class name or null for global)
 * - reference_id: Polymorphic ID (model ID or null for global)
 * - timestamps: created_at, updated_at
 *
 *
 * @property int $id
 * @property string $key
 * @property string $value
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Setting extends Model
{
    protected $table = 'schema_settings';

    protected $fillable = [
        'key',
        'value',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Allow dynamic table name from config
        $this->table = config('schema-settings.table_name', 'schema_settings');
    }

    /**
     * Get the reference model (polymorphic relation).
     *
     * Returns the model instance this setting is scoped to (e.g., a User instance).
     * Null for global settings.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    /**
     * Query scope to filter only global settings.
     *
     * Global settings have null reference_type and reference_id.
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('reference_type')->whereNull('reference_id');
    }

    /**
     * Query scope to filter settings for a specific model instance.
     *
     * Finds settings scoped to a particular model (e.g., User ID 1).
     *
     * @param  Model  $model  The Eloquent model instance to scope by
     */
    public function scopeForModel(Builder $query, Model $model): Builder
    {
        return $query->where('reference_type', $model::class)
            ->where('reference_id', $model->getKey());
    }

    /**
     * Query scope to filter by setting key.
     *
     * @param  string  $key  The setting key
     */
    public function scopeKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }
}
