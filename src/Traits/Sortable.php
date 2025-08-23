<?php

namespace Litepie\Database\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Advanced Sortable trait with multiple sorting strategies.
 */
trait Sortable
{
    /**
     * Default sort order configuration.
     *
     * @var array
     */
    protected array $sortable = [];

    /**
     * Default sort direction.
     *
     * @var string
     */
    protected string $defaultSortDirection = 'asc';

    /**
     * Position column name for manual ordering.
     *
     * @var string
     */
    protected string $positionColumn = 'position';

    /**
     * Initialize the sortable trait.
     *
     * @return void
     */
    public function initializeSortable(): void
    {
        if (empty($this->sortable)) {
            $this->sortable = ['created_at' => 'desc'];
        }
    }

    /**
     * Scope for sorting by a specific field.
     *
     * @param Builder $query
     * @param string $field
     * @param string $direction
     * @return Builder
     */
    public function scopeSortBy(Builder $query, string $field, string $direction = 'asc'): Builder
    {
        $direction = strtolower($direction);
        
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = $this->defaultSortDirection;
        }

        // Handle relationship sorting
        if (str_contains($field, '.')) {
            return $this->sortByRelation($query, $field, $direction);
        }

        // Handle JSON field sorting
        if (str_contains($field, '->')) {
            return $query->orderByRaw("JSON_EXTRACT({$field}) {$direction}");
        }

        return $query->orderBy($field, $direction);
    }

    /**
     * Scope for multiple column sorting.
     *
     * @param Builder $query
     * @param array $sorts
     * @return Builder
     */
    public function scopeSortByMultiple(Builder $query, array $sorts): Builder
    {
        foreach ($sorts as $field => $direction) {
            if (is_numeric($field)) {
                // Handle ['field', 'direction'] format
                $field = $direction;
                $direction = $this->defaultSortDirection;
            }
            
            $query->sortBy($field, $direction);
        }

        return $query;
    }

    /**
     * Scope for sorting by position.
     *
     * @param Builder $query
     * @param string $direction
     * @return Builder
     */
    public function scopeSortByPosition(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy($this->positionColumn, $direction);
    }

    /**
     * Scope for sorting by popularity (views, likes, etc.).
     *
     * @param Builder $query
     * @param string $column
     * @param string $direction
     * @return Builder
     */
    public function scopeSortByPopularity(Builder $query, string $column = 'views', string $direction = 'desc'): Builder
    {
        return $query->orderBy($column, $direction);
    }

    /**
     * Scope for sorting by relationship count.
     *
     * @param Builder $query
     * @param string $relation
     * @param string $direction
     * @return Builder
     */
    public function scopeSortByCount(Builder $query, string $relation, string $direction = 'desc'): Builder
    {
        return $query->withCount($relation)->orderBy($relation . '_count', $direction);
    }

    /**
     * Scope for random sorting.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeSortRandom(Builder $query): Builder
    {
        return $query->inRandomOrder();
    }

    /**
     * Scope for custom sorting with case statement.
     *
     * @param Builder $query
     * @param string $field
     * @param array $order
     * @return Builder
     */
    public function scopeSortByCustomOrder(Builder $query, string $field, array $order): Builder
    {
        $cases = [];
        foreach ($order as $index => $value) {
            $cases[] = "WHEN {$field} = '{$value}' THEN {$index}";
        }
        
        $caseStatement = 'CASE ' . implode(' ', $cases) . ' ELSE 999 END';
        
        return $query->orderByRaw($caseStatement);
    }

    /**
     * Move the model to a specific position.
     *
     * @param int $position
     * @return bool
     */
    public function moveToPosition(int $position): bool
    {
        $currentPosition = $this->{$this->positionColumn};
        
        if ($currentPosition === $position) {
            return true;
        }

        // Update positions of other records
        if ($currentPosition < $position) {
            // Moving down
            $this->newQuery()
                ->where($this->positionColumn, '>', $currentPosition)
                ->where($this->positionColumn, '<=', $position)
                ->decrement($this->positionColumn);
        } else {
            // Moving up
            $this->newQuery()
                ->where($this->positionColumn, '>=', $position)
                ->where($this->positionColumn, '<', $currentPosition)
                ->increment($this->positionColumn);
        }

        // Update current record
        $this->{$this->positionColumn} = $position;
        return $this->save();
    }

    /**
     * Move the model up by one position.
     *
     * @return bool
     */
    public function moveUp(): bool
    {
        $currentPosition = $this->{$this->positionColumn};
        
        if ($currentPosition <= 1) {
            return false;
        }

        return $this->moveToPosition($currentPosition - 1);
    }

    /**
     * Move the model down by one position.
     *
     * @return bool
     */
    public function moveDown(): bool
    {
        $currentPosition = $this->{$this->positionColumn};
        $maxPosition = $this->getMaxPosition();
        
        if ($currentPosition >= $maxPosition) {
            return false;
        }

        return $this->moveToPosition($currentPosition + 1);
    }

    /**
     * Move the model to the top position.
     *
     * @return bool
     */
    public function moveToTop(): bool
    {
        return $this->moveToPosition(1);
    }

    /**
     * Move the model to the bottom position.
     *
     * @return bool
     */
    public function moveToBottom(): bool
    {
        return $this->moveToPosition($this->getMaxPosition() + 1);
    }

    /**
     * Get the maximum position value.
     *
     * @return int
     */
    protected function getMaxPosition(): int
    {
        return $this->newQuery()->max($this->positionColumn) ?? 0;
    }

    /**
     * Sort by relationship field.
     *
     * @param Builder $query
     * @param string $field
     * @param string $direction
     * @return Builder
     */
    protected function sortByRelation(Builder $query, string $field, string $direction): Builder
    {
        $parts = explode('.', $field, 2);
        $relation = $parts[0];
        $column = $parts[1];

        return $query->join(
            $this->getRelationTable($relation),
            $this->getTable() . '.' . $this->getForeignKey($relation),
            '=',
            $this->getRelationTable($relation) . '.id'
        )->orderBy($this->getRelationTable($relation) . '.' . $column, $direction);
    }

    /**
     * Get relation table name.
     *
     * @param string $relation
     * @return string
     */
    protected function getRelationTable(string $relation): string
    {
        return $this->$relation()->getRelated()->getTable();
    }

    /**
     * Get foreign key for relation.
     *
     * @param string $relation
     * @return string
     */
    protected function getForeignKey(string $relation): string
    {
        return $relation . '_id';
    }

    /**
     * Get sortable fields.
     *
     * @return array
     */
    public function getSortableFields(): array
    {
        return array_keys($this->sortable);
    }

    /**
     * Check if a field is sortable.
     *
     * @param string $field
     * @return bool
     */
    public function isSortable(string $field): bool
    {
        return in_array($field, $this->getSortableFields());
    }

    /**
     * Apply default sorting.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeDefaultSort(Builder $query): Builder
    {
        foreach ($this->sortable as $field => $direction) {
            $query->sortBy($field, $direction);
        }

        return $query;
    }

    /**
     * Apply sorting from request parameters.
     *
     * @param Builder $query
     * @param array $params
     * @return Builder
     */
    public function scopeSortFromRequest(Builder $query, array $params = []): Builder
    {
        $sortBy = $params['sort_by'] ?? $params['sort'] ?? null;
        $sortDirection = $params['sort_direction'] ?? $params['direction'] ?? $this->defaultSortDirection;

        if ($sortBy && $this->isSortable($sortBy)) {
            return $query->sortBy($sortBy, $sortDirection);
        }

        // Apply multiple sorts if provided
        if (isset($params['sorts']) && is_array($params['sorts'])) {
            return $query->sortByMultiple($params['sorts']);
        }

        return $query->defaultSort();
    }

    /**
     * Reorder all models by their position.
     *
     * @return void
     */
    public static function reorder(): void
    {
        $models = static::orderBy((new static())->positionColumn)->get();
        
        foreach ($models as $index => $model) {
            $model->update([
                (new static())->positionColumn => $index + 1
            ]);
        }
    }

    /**
     * Set the position column name.
     *
     * @param string $column
     * @return $this
     */
    public function setPositionColumn(string $column): static
    {
        $this->positionColumn = $column;
        return $this;
    }
}
