<?php

namespace Litepie\Database\Traits;

trait BulkActions
{
    /**
     * Bulk update records by IDs.
     */
    public static function bulkUpdate(array $ids, array $attributes)
    {
        return static::whereIn('id', $ids)->update($attributes);
    }

    /**
     * Bulk delete records by IDs.
     */
    public static function bulkDelete(array $ids)
    {
        return static::whereIn('id', $ids)->delete();
    }

    /**
     * Bulk archive records by IDs (if Archivable trait is used).
     */
    public static function bulkArchive(array $ids)
    {
        return static::whereIn('id', $ids)->each->archive();
    }

    /**
     * Bulk restore records by IDs (if Archivable trait is used).
     */
    public static function bulkRestore(array $ids)
    {
        return static::whereIn('id', $ids)->each->unArchive();
    }
}
