<?php

namespace Litepie\Database\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Versionable trait for tracking model version history.
 * 
 * This trait automatically creates versions of your models, allowing you to:
 * - Track all changes to a model over time
 * - Rollback to previous versions
 * - Compare versions
 * - View version history with metadata
 */
trait Versionable
{
    /**
     * Maximum number of versions to keep (0 = unlimited).
     *
     * @var int
     */
    protected int $maxVersions = 0;

    /**
     * Columns to exclude from versioning.
     *
     * @var array
     */
    protected array $versionableExclude = ['created_at', 'updated_at'];

    /**
     * Whether to automatically create versions on update.
     *
     * @var bool
     */
    protected bool $autoVersioning = true;

    /**
     * Whether to version on create.
     *
     * @var bool
     */
    protected bool $versionOnCreate = false;

    /**
     * Boot the versionable trait.
     *
     * @return void
     */
    public static function bootVersionable(): void
    {
        static::created(function (Model $model) {
            if ($model->shouldVersionOnCreate()) {
                $model->createVersion('Initial version');
            }
        });

        static::updated(function (Model $model) {
            if ($model->shouldAutoVersion()) {
                $model->createVersion('Auto-saved version');
            }
        });

        static::deleted(function (Model $model) {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                $model->versions()->delete();
            }
        });
    }

    /**
     * Get all versions for this model.
     *
     * @return MorphMany
     */
    public function versions(): MorphMany
    {
        return $this->morphMany(
            config('litepie-database.versionable.model', ModelVersion::class),
            'versionable'
        )->orderBy('version_number', 'desc');
    }

    /**
     * Create a new version of the current model state.
     *
     * @param string|null $reason
     * @param Model|int|null $user
     * @param array $metadata
     * @return Model|null
     */
    public function createVersion(?string $reason = null, mixed $user = null, array $metadata = []): ?Model
    {
        $versionData = $this->getVersionableData();
        
        if (empty($versionData)) {
            return null;
        }

        $versionNumber = $this->getNextVersionNumber();

        $version = $this->versions()->create([
            'version_number' => $versionNumber,
            'data' => $versionData,
            'reason' => $reason,
            'user_id' => $this->getUserId($user),
            'user_type' => $this->getUserType($user),
            'metadata' => $metadata,
            'hash' => $this->generateVersionHash($versionData),
        ]);

        $this->pruneOldVersions();

        return $version;
    }

    /**
     * Restore the model to a specific version.
     *
     * @param int $versionNumber
     * @param bool $createVersion
     * @return bool
     */
    public function rollbackToVersion(int $versionNumber, bool $createVersion = true): bool
    {
        $version = $this->versions()->where('version_number', $versionNumber)->first();

        if (!$version) {
            return false;
        }

        if ($createVersion) {
            $this->createVersion("Rollback to version {$versionNumber}");
        }

        $this->disableAutoVersioning();
        
        $data = $version->data;
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }

        $result = $this->save();
        
        $this->enableAutoVersioning();

        return $result;
    }

    /**
     * Rollback to the previous version.
     *
     * @param bool $createVersion
     * @return bool
     */
    public function rollbackToPrevious(bool $createVersion = true): bool
    {
        $previousVersion = $this->getPreviousVersion();

        if (!$previousVersion) {
            return false;
        }

        return $this->rollbackToVersion($previousVersion->version_number, $createVersion);
    }

    /**
     * Get a specific version by number.
     *
     * @param int $versionNumber
     * @return Model|null
     */
    public function getVersion(int $versionNumber): ?Model
    {
        return $this->versions()->where('version_number', $versionNumber)->first();
    }

    /**
     * Get the latest version.
     *
     * @return Model|null
     */
    public function getLatestVersion(): ?Model
    {
        return $this->versions()->first();
    }

    /**
     * Get the previous version.
     *
     * @return Model|null
     */
    public function getPreviousVersion(): ?Model
    {
        return $this->versions()->skip(1)->first();
    }

    /**
     * Get all versions ordered by version number.
     *
     * @param string $direction
     * @return Collection
     */
    public function getVersionHistory(string $direction = 'desc'): Collection
    {
        return $this->versions()->orderBy('version_number', $direction)->get();
    }

    /**
     * Compare two versions and return differences.
     *
     * @param int $versionA
     * @param int $versionB
     * @return array
     */
    public function compareVersions(int $versionA, int $versionB): array
    {
        $versionObjA = $this->getVersion($versionA);
        $versionObjB = $this->getVersion($versionB);

        if (!$versionObjA || !$versionObjB) {
            return [];
        }

        $dataA = $versionObjA->data;
        $dataB = $versionObjB->data;

        $differences = [];
        $allKeys = array_unique(array_merge(array_keys($dataA), array_keys($dataB)));

        foreach ($allKeys as $key) {
            $valueA = $dataA[$key] ?? null;
            $valueB = $dataB[$key] ?? null;

            if ($valueA !== $valueB) {
                $differences[$key] = [
                    'version_' . $versionA => $valueA,
                    'version_' . $versionB => $valueB,
                    'changed' => true,
                ];
            }
        }

        return $differences;
    }

    /**
     * Compare current state with a specific version.
     *
     * @param int $versionNumber
     * @return array
     */
    public function compareWithVersion(int $versionNumber): array
    {
        $version = $this->getVersion($versionNumber);

        if (!$version) {
            return [];
        }

        $currentData = $this->getVersionableData();
        $versionData = $version->data;

        $differences = [];
        $allKeys = array_unique(array_merge(array_keys($currentData), array_keys($versionData)));

        foreach ($allKeys as $key) {
            $currentValue = $currentData[$key] ?? null;
            $versionValue = $versionData[$key] ?? null;

            if ($currentValue !== $versionValue) {
                $differences[$key] = [
                    'current' => $currentValue,
                    'version_' . $versionNumber => $versionValue,
                    'changed' => true,
                ];
            }
        }

        return $differences;
    }

    /**
     * Get the number of versions.
     *
     * @return int
     */
    public function getVersionCount(): int
    {
        return $this->versions()->count();
    }

    /**
     * Delete all versions.
     *
     * @return bool
     */
    public function deleteAllVersions(): bool
    {
        return $this->versions()->delete();
    }

    /**
     * Delete versions older than specified number.
     *
     * @param int $keepLast
     * @return int
     */
    public function deleteOldVersions(int $keepLast = 10): int
    {
        $versionsToDelete = $this->versions()
            ->skip($keepLast)
            ->pluck('id');

        return $this->versions()->whereIn('id', $versionsToDelete)->delete();
    }

    /**
     * Get versions created by a specific user.
     *
     * @param Model|int $user
     * @return Collection
     */
    public function getVersionsByUser(mixed $user): Collection
    {
        $userId = $this->getUserId($user);
        
        return $this->versions()
            ->where('user_id', $userId)
            ->get();
    }

    /**
     * Get versions with a specific reason.
     *
     * @param string $reason
     * @return Collection
     */
    public function getVersionsByReason(string $reason): Collection
    {
        return $this->versions()
            ->where('reason', 'like', "%{$reason}%")
            ->get();
    }

    /**
     * Check if model has been versioned.
     *
     * @return bool
     */
    public function hasVersions(): bool
    {
        return $this->getVersionCount() > 0;
    }

    /**
     * Check if a specific version exists.
     *
     * @param int $versionNumber
     * @return bool
     */
    public function hasVersion(int $versionNumber): bool
    {
        return $this->versions()->where('version_number', $versionNumber)->exists();
    }

    /**
     * Get version statistics.
     *
     * @return array
     */
    public function getVersionStats(): array
    {
        $versions = $this->versions()->get();

        return [
            'total_versions' => $versions->count(),
            'first_version' => $versions->last()?->created_at,
            'latest_version' => $versions->first()?->created_at,
            'unique_users' => $versions->pluck('user_id')->unique()->count(),
            'versions_by_reason' => $versions->groupBy('reason')->map->count(),
        ];
    }

    /**
     * Preview what would change if rolled back to version.
     *
     * @param int $versionNumber
     * @return array
     */
    public function previewRollback(int $versionNumber): array
    {
        return $this->compareWithVersion($versionNumber);
    }

    /**
     * Temporarily disable auto-versioning.
     *
     * @return $this
     */
    public function disableAutoVersioning(): static
    {
        $this->autoVersioning = false;
        return $this;
    }

    /**
     * Enable auto-versioning.
     *
     * @return $this
     */
    public function enableAutoVersioning(): static
    {
        $this->autoVersioning = true;
        return $this;
    }

    /**
     * Set maximum versions to keep.
     *
     * @param int $max
     * @return $this
     */
    public function setMaxVersions(int $max): static
    {
        $this->maxVersions = $max;
        return $this;
    }

    /**
     * Get versionable data (excluding specified columns).
     *
     * @return array
     */
    protected function getVersionableData(): array
    {
        $data = $this->getAttributes();
        
        // Remove excluded columns
        foreach ($this->getVersionableExclude() as $column) {
            unset($data[$column]);
        }

        // Remove primary key
        unset($data[$this->getKeyName()]);

        return $data;
    }

    /**
     * Get the next version number.
     *
     * @return int
     */
    protected function getNextVersionNumber(): int
    {
        $lastVersion = $this->versions()->max('version_number');
        return ($lastVersion ?? 0) + 1;
    }

    /**
     * Generate hash for version data integrity.
     *
     * @param array $data
     * @return string
     */
    protected function generateVersionHash(array $data): string
    {
        return hash('sha256', serialize($data));
    }

    /**
     * Prune old versions if max limit is set.
     *
     * @return void
     */
    protected function pruneOldVersions(): void
    {
        if ($this->maxVersions > 0) {
            $this->deleteOldVersions($this->maxVersions);
        }
    }

    /**
     * Get user ID from user model or integer.
     *
     * @param mixed $user
     * @return int|null
     */
    protected function getUserId(mixed $user): ?int
    {
        if ($user instanceof Model) {
            return $user->getKey();
        }

        if (is_numeric($user)) {
            return (int) $user;
        }

        return null;
    }

    /**
     * Get user type from user model.
     *
     * @param mixed $user
     * @return string|null
     */
    protected function getUserType(mixed $user): ?string
    {
        if ($user instanceof Model) {
            return get_class($user);
        }

        return null;
    }

    /**
     * Get columns excluded from versioning.
     *
     * @return array
     */
    protected function getVersionableExclude(): array
    {
        return array_merge(
            $this->versionableExclude,
            ['deleted_at', 'archived_at']
        );
    }

    /**
     * Check if should auto-version on update.
     *
     * @return bool
     */
    protected function shouldAutoVersion(): bool
    {
        return $this->autoVersioning && $this->isDirty();
    }

    /**
     * Check if should version on create.
     *
     * @return bool
     */
    protected function shouldVersionOnCreate(): bool
    {
        return $this->versionOnCreate;
    }
}

/**
 * Model Version class for storing version data.
 * 
 * Migration:
 * Schema::create('model_versions', function (Blueprint $table) {
 *     $table->id();
 *     $table->morphs('versionable');
 *     $table->integer('version_number');
 *     $table->json('data');
 *     $table->string('reason')->nullable();
 *     $table->unsignedBigInteger('user_id')->nullable();
 *     $table->string('user_type')->nullable();
 *     $table->json('metadata')->nullable();
 *     $table->string('hash')->nullable();
 *     $table->timestamps();
 *     
 *     $table->index(['versionable_type', 'versionable_id']);
 *     $table->index('version_number');
 *     $table->index('user_id');
 * });
 */
class ModelVersion extends Model
{
    protected $fillable = [
        'versionable_type',
        'versionable_id',
        'version_number',
        'data',
        'reason',
        'user_id',
        'user_type',
        'metadata',
        'hash',
    ];

    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
        'version_number' => 'integer',
    ];

    /**
     * Get the owning versionable model.
     */
    public function versionable()
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created this version.
     */
    public function user()
    {
        return $this->morphTo('user');
    }
}
