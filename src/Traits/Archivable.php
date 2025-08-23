<?php

namespace Litepie\Database\Traits;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Scopes\ArchivableScope;

/**
 * Advanced Archivable trait with enhanced functionality.
 *
 * @method static Builder|static withArchived(bool $withArchived = true)
 * @method static Builder|static onlyArchived()
 * @method static Builder|static withoutArchived()
 * @method static int archive()
 * @method static int unArchive()
 */
trait Archivable
{
    /**
     * Indicates if the model should use archives.
     *
     * @var bool
     */
    public bool $archives = true;

    /**
     * The reason for archiving the model.
     *
     * @var string|null
     */
    protected ?string $archiveReason = null;

    /**
     * Boot the archiving trait for a model.
     *
     * @return void
     */
    public static function bootArchivable(): void
    {
        static::addGlobalScope(new ArchivableScope());
    }

    /**
     * Initialize the archivable trait for an instance.
     *
     * @return void
     */
    public function initializeArchivable(): void
    {
        if (!isset($this->casts[$this->getArchivedAtColumn()])) {
            $this->casts[$this->getArchivedAtColumn()] = 'datetime';
        }

        if (!isset($this->casts[$this->getArchivedByColumn()])) {
            $this->casts[$this->getArchivedByColumn()] = 'string';
        }

        $this->addObservableEvents([
            'archiving',
            'archived',
            'unArchiving',
            'unArchived',
        ]);
    }

    /**
     * Archive the model with optional reason and user.
     *
     * @param string|null $reason
     * @param mixed $user
     * @return bool|null
     * @throws Exception
     */
    public function archive(?string $reason = null, mixed $user = null): ?bool
    {
        $this->mergeAttributesFromClassCasts();

        if (is_null($this->getKeyName())) {
            throw new Exception('No primary key defined on model.');
        }

        if (!$this->exists) {
            return null;
        }

        if ($this->fireModelEvent('archiving') === false) {
            return false;
        }

        $this->touchOwners();

        if ($reason) {
            $this->archiveReason = $reason;
        }

        $this->runArchive($user);

        $this->fireModelEvent('archived', false);

        return true;
    }

    /**
     * Perform the actual archive query on this model instance.
     *
     * @param mixed $user
     * @return void
     */
    protected function runArchive(mixed $user = null): void
    {
        $query = $this->setKeysForSaveQuery($this->newModelQuery());
        $time = $this->freshTimestamp();

        $columns = [
            $this->getArchivedAtColumn() => $this->fromDateTime($time)
        ];

        $this->{$this->getArchivedAtColumn()} = $time;

        // Set archived by user if provided
        if ($user) {
            $archivedByColumn = $this->getArchivedByColumn();
            $userId = is_object($user) && method_exists($user, 'getKey') ? $user->getKey() : $user;
            $columns[$archivedByColumn] = $userId;
            $this->{$archivedByColumn} = $userId;
        }

        // Set archive reason if provided
        if ($this->archiveReason && $this->hasArchivedReasonColumn()) {
            $reasonColumn = $this->getArchivedReasonColumn();
            $columns[$reasonColumn] = $this->archiveReason;
            $this->{$reasonColumn} = $this->archiveReason;
        }

        if ($this->usesTimestamps() && !is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;
            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $query->update($columns);
        $this->syncOriginalAttributes(array_keys($columns));
    }

    /**
     * Restore the model from archive.
     *
     * @param mixed $user
     * @return bool|null
     */
    public function unArchive(mixed $user = null): ?bool
    {
        if ($this->fireModelEvent('unArchiving') === false) {
            return false;
        }

        $this->{$this->getArchivedAtColumn()} = null;

        // Clear archived by user
        if ($this->hasArchivedByColumn()) {
            $this->{$this->getArchivedByColumn()} = null;
        }

        // Clear archive reason
        if ($this->hasArchivedReasonColumn()) {
            $this->{$this->getArchivedReasonColumn()} = null;
        }

        $this->exists = true;
        $result = $this->save();

        $this->fireModelEvent('unArchived', false);

        return $result;
    }

    /**
     * Determine if the model instance has been archived.
     *
     * @return bool
     */
    public function isArchived(): bool
    {
        return !is_null($this->{$this->getArchivedAtColumn()});
    }

    /**
     * Get the archived reason for this model.
     *
     * @return string|null
     */
    public function getArchiveReason(): ?string
    {
        return $this->hasArchivedReasonColumn() 
            ? $this->{$this->getArchivedReasonColumn()} 
            : null;
    }

    /**
     * Get the user who archived this model.
     *
     * @return mixed
     */
    public function getArchivedBy(): mixed
    {
        return $this->hasArchivedByColumn() 
            ? $this->{$this->getArchivedByColumn()} 
            : null;
    }

    /**
     * Set the archive reason.
     *
     * @param string $reason
     * @return $this
     */
    public function setArchiveReason(string $reason): static
    {
        $this->archiveReason = $reason;
        return $this;
    }

    /**
     * Archive multiple models by their IDs.
     *
     * @param array $ids
     * @param string|null $reason
     * @param mixed $user
     * @return int
     */
    public static function archiveByIds(array $ids, ?string $reason = null, mixed $user = null): int
    {
        $query = static::whereIn('id', $ids);
        $columns = [
            (new static())->getArchivedAtColumn() => now()
        ];

        if ($user) {
            $userId = is_object($user) && method_exists($user, 'getKey') ? $user->getKey() : $user;
            $columns[(new static())->getArchivedByColumn()] = $userId;
        }

        if ($reason && (new static())->hasArchivedReasonColumn()) {
            $columns[(new static())->getArchivedReasonColumn()] = $reason;
        }

        return $query->update($columns);
    }

    /**
     * Register model event callbacks.
     */
    public static function archiving(\Closure|string $callback): void
    {
        static::registerModelEvent('archiving', $callback);
    }

    public static function archived(\Closure|string $callback): void
    {
        static::registerModelEvent('archived', $callback);
    }

    public static function unArchiving(\Closure|string $callback): void
    {
        static::registerModelEvent('unArchiving', $callback);
    }

    public static function unArchived(\Closure|string $callback): void
    {
        static::registerModelEvent('unArchived', $callback);
    }

    /**
     * Get the name of the "archived at" column.
     *
     * @return string
     */
    public function getArchivedAtColumn(): string
    {
        return defined('static::ARCHIVED_AT') ? static::ARCHIVED_AT : 'archived_at';
    }

    /**
     * Get the name of the "archived by" column.
     *
     * @return string
     */
    public function getArchivedByColumn(): string
    {
        return defined('static::ARCHIVED_BY') ? static::ARCHIVED_BY : 'archived_by';
    }

    /**
     * Get the name of the "archived reason" column.
     *
     * @return string
     */
    public function getArchivedReasonColumn(): string
    {
        return defined('static::ARCHIVED_REASON') ? static::ARCHIVED_REASON : 'archived_reason';
    }

    /**
     * Get the fully qualified "archived at" column.
     *
     * @return string
     */
    public function getQualifiedArchivedAtColumn(): string
    {
        return $this->qualifyColumn($this->getArchivedAtColumn());
    }

    /**
     * Check if the model has archived by column.
     *
     * @return bool
     */
    protected function hasArchivedByColumn(): bool
    {
        return $this->getConnection()
            ->getSchemaBuilder()
            ->hasColumn($this->getTable(), $this->getArchivedByColumn());
    }

    /**
     * Check if the model has archived reason column.
     *
     * @return bool
     */
    protected function hasArchivedReasonColumn(): bool
    {
        return $this->getConnection()
            ->getSchemaBuilder()
            ->hasColumn($this->getTable(), $this->getArchivedReasonColumn());
    }
}
