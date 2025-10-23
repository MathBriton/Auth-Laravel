<?php

namespace App\Models;

use App\Traits\Relationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ActivityLog
 *
 * @property int $id
 * @property int $user_id
 * @property string $affected_table
 * @property int $affected_id
 * @property string $action
 * @property string $description
 * @property array $changed_fields
 * @property array $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property-read \App\Models\User $user
 *
 * */
class ActivityLog extends Model
{
    use HasFactory, Relationships;

    protected $fillable = [
        'user_id',
        'affected_table',
        'affected_id',
        'action',
        'description',
        'changed_fields',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'changed_fields' => 'array',
    ];

    protected $hidden = ['updated_at'];

    /* ========================= */
    /*      RELACIONAMENTOS      */
    /* ========================= */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    /**
     * Adiciona traduções aos campos alterados antes de salvar
     *
     * @param  array<int|string,array{field:string,old:mixed,new:mixed}>|null  $changes
     * @return array<int|string,array{field:string,old:mixed,new:mixed,translated_field:string}>|null
     */
    private function addTranslationsToChanges(?array $changes): ?array
    {
        if (!$changes) {
            return null;
        }

        $translations = trans('validation.attributes');

        // Garante array numérico
        $changes = !isset($changes[0]) ? [$changes] : $changes;

        $result = array_map(function (array $change) use ($translations) {
            if (!isset($change['field'])) {
                return [
                    'field' => '',
                    'old' => null,
                    'new' => null,
                    'translated_field' => '',
                ];
            }

            $fieldParts = explode('.', $change['field']);
            $fieldKey = end($fieldParts);

            return [
                'field' => $change['field'],
                'translated_field' => $translations[$fieldKey] ?? $fieldKey,
                'old' => $change['old'] ?? null,
                'new' => $change['new'] ?? null,
            ];
        }, $changes);

        return $result;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $payload = request()->all();

            $redactedFields = ['password', 'password_confirmation'];

            foreach ($redactedFields as $field) {
                if (isset($payload[$field])) {
                    $payload[$field] = '********';
                }
            }

            $metadata = [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'payload' => $payload,
                'method' => request()->method(),
            ];

            $model->metadata = $metadata;

            // Adiciona traduções aos campos alterados
            if ($model->changed_fields) {
                $model->changed_fields = $model->addTranslationsToChanges($model->changed_fields);
            }
        });
    }
}
