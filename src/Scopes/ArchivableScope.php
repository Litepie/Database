<?php

namespace Litepie\Database\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Enhanced Archivable scope with advanced query methods.
 */
class ArchivableScope implements Scope
{
    /**
     * All of the extensions to be added to the builder.
     *
     * @var array
     */
    protected array $extensions = [
        'Archive', 
        'UnArchive', 
        'WithArchived', 
        'WithoutArchived', 
        'OnlyArchived',
        'ArchiveWhere',
        'ArchiveBetween',
        'RecentlyArchived',
        'ArchiveCount'
    ];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (method_exists($model, 'getQualifiedArchivedAtColumn')) {
            $builder->whereNull($model->getQualifiedArchivedAtColumn());
        }
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param Builder $builder
     * @return void
     */
    public function extend(Builder $builder): void
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    /**
     * Get the "archived at" column for the builder.
     *
     * @param Builder $builder
     * @return string
     */
    protected function getArchivedAtColumn(Builder $builder): string
    {
        if (count((array) $builder->getQuery()->joins) > 0) {
            return $builder->getModel()->getQualifiedArchivedAtColumn();
        }

        return $builder->getModel()->getArchivedAtColumn();
    }

    /**
     * Add the archive extension to the builder.
     *
     * @param Builder $builder
     * @return void
     */
    protected function addArchive(Builder $builder): void
    {
        $builder->macro('archive', function (Builder $builder, ?string $reason = null, mixed $user = null) {
            $column = $this->getArchivedAtColumn($builder);
            $model = $builder->getModel();
            
            $updates = [
                $column => $model->freshTimestampString(),
            ];

            // Add archived_by if user provided and column exists
            if ($user && method_exists($model, 'getArchivedByColumn')) {
                $archivedByColumn = $model->getArchivedByColumn();
                if ($builder->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), $archivedByColumn)) {
                    $userId = is_object($user) && method_exists($user, 'getKey') ? $user->getKey() : $user;
                    $updates[$archivedByColumn] = $userId;
                }
            }

            // Add reason if provided and column exists
            if ($reason && method_exists($model, 'getArchivedReasonColumn')) {
                $reasonColumn = $model->getArchivedReasonColumn();
                if ($builder->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), $reasonColumn)) {
                    $updates[$reasonColumn] = $reason;
                }
            }

            return $builder->update($updates);
        });
    }

    /**
     * Add the un-archive extension to the builder.
     *
     * @param Builder $builder
     * @return void
     */
    protected function addUnArchive(Builder $builder): void
    {
        $builder->macro('unArchive', function (Builder $builder) {
            $builder->withArchived();
            $model = $builder->getModel();
            $column = $this->getArchivedAtColumn($builder);

            $updates = [$column => null];

            // Clear archived_by if column exists
            if (method_exists($model, 'getArchivedByColumn')) {
                $archivedByColumn = $model->getArchivedByColumn();
                if ($builder->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), $archivedByColumn)) {
                    $updates[$archivedByColumn] = null;
                }
            }

            // Clear reason if column exists
            if (method_exists($model, 'getArchivedReasonColumn')) {
                $reasonColumn = $model->getArchivedReasonColumn();
                if ($builder->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), $reasonColumn)) {
                    $updates[$reasonColumn] = null;
                }
            }

            return $builder->update($updates);
        });
    }

    /**
     * Add the with-archived extension to the builder.
     *
     * @param Builder $builder
     * @return void
     */
    protected function addWithArchived(Builder $builder): void
    {
        $builder->macro('withArchived', function (Builder $builder, bool $withArchived = true) {
            if (!$withArchived) {
                return $builder->withoutArchived();
            }

            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Add the without-archived extension to the builder.
     *
     * @param Builder $builder
     * @return void
     */
    protected function addWithoutArchived(Builder $builder): void
    {
        $builder->macro('withoutArchived', function (Builder $builder) {
            $model = $builder->getModel();

            return $builder->withoutGlobalScope($this)->whereNull(
                $model->getQualifiedArchivedAtColumn()
            );
        });
    }

    /**
     * Add the only-archived extension to the builder.
     *
     * @param Builder $builder
     * @return void
     */
    protected function addOnlyArchived(Builder $builder): void
    {
        $builder->macro('onlyArchived', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->whereNotNull(
                $model->getQualifiedArchivedAtColumn()
            );

            return $builder;
        });
    }

    /**
     * Add the archive-where extension to the builder.
     *
     * @param Builder $builder
     * @return void
     */
    protected function addArchiveWhere(Builder $builder): void
    {
        $builder->macro('archivedWhere', function (Builder $builder, string $column, mixed $operator = null, mixed $value = null) {
            return $builder->onlyArchived()->where($column, $operator, $value);
        });
    }

    /**
     * Add the archive-between extension to the builder.
     *
     * @param Builder $builder
     * @return void
     */
    protected function addArchiveBetween(Builder $builder): void
    {
        $builder->macro('archivedBetween', function (Builder $builder, array $dates) {
            $column = $this->getArchivedAtColumn($builder);
            
            return $builder->onlyArchived()->whereBetween($column, $dates);
        });
    }

    /**
     * Add the recently-archived extension to the builder.
     *
     * @param Builder $builder
     * @return void
     */
    protected function addRecentlyArchived(Builder $builder): void
    {
        $builder->macro('recentlyArchived', function (Builder $builder, int $days = 7) {
            $column = $this->getArchivedAtColumn($builder);
            $date = now()->subDays($days);
            
            return $builder->onlyArchived()->where($column, '>=', $date);
        });
    }

    /**
     * Add the archive-count extension to the builder.
     *
     * @param Builder $builder
     * @return void
     */
    protected function addArchiveCount(Builder $builder): void
    {
        $builder->macro('archiveCount', function (Builder $builder) {
            return $builder->onlyArchived()->count();
        });
    }
}
