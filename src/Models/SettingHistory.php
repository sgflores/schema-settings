<?php

namespace SgFlores\SchemaSetting\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * SettingHistory Model
 *
 * Eloquent model for the schema_settings_history audit trail table.
 * Records all changes to settings including who changed what and when.
 *
 * Database Structure:
 * - id: Primary key
 * - key: Setting identifier
 * - old_value: Previous value (null for creates)
 * - new_value: New value (null for deletes)
 * - reference_type: Polymorphic type (model class or null)
 * - reference_id: Polymorphic ID (model ID or null)
 * - user_type: User polymorphic type (who made the change)
 * - user_id: User polymorphic ID
 * - action: 'created', 'updated', or 'deleted'
 * - created_at: When the change occurred
 *
 *
 * @property int $id
 * @property string $key
 * @property string|null $old_value
 * @property string|null $new_value
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $user_type
 * @property int|null $user_id
 * @property string $action
 * @property Carbon $created_at
 */
class SettingHistory extends Model
{
    protected $table = 'schema_settings_history';

    public $timestamps = false;

    protected $fillable = [
        'key',
        'old_value',
        'new_value',
        'reference_type',
        'reference_id',
        'user_type',
        'user_id',
        'action',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Allow dynamic table name from config
        $this->table = config('schema-settings.audit.table_name', 'schema_settings_history');
    }

    /**
     * Get the reference model this history entry relates to.
     *
     * Returns the model instance this setting was scoped to at the time of change.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    /**
     * Get the user who made this change.
     *
     * Returns the authenticated user instance who triggered this change.
     * Null if change was made programmatically without authentication.
     */
    public function user(): MorphTo
    {
        return $this->morphTo('user');
    }

    /**
     * Query scope to filter by setting key.
     *
     * Useful for retrieving the complete change history of a specific setting.
     *
     * @param  string  $key  The setting key
     */
    public function scopeKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    /**
     * Query scope to filter by action type.
     *
     * Actions are: 'created', 'updated', or 'deleted'
     *
     * @param  string  $action  The action type to filter by
     */
    public function scopeAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }
}
