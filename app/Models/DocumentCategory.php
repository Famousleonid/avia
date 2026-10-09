<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentCategory extends Model
{
    use SoftDeletes;

    protected $fillable = ['name'];

    public static function libraryCategories(bool $includeRemoved = false)
    {
        $query = static::query();
        if ($includeRemoved) $query->withTrashed();
        return $query->where('key', '<>', 'video')->orderBy('name')->get()
            ->push((new static())->forceFill(['key' => 'video', 'name' => 'Video']));
    }

    protected static function booted(): void
    {
        static::creating(function (self $category) {
            $category->key = $category->key ?: 'category_'.Str::uuid();
        });
        static::deleting(function (self $category) {
            if ($category->key === 'general') {
                throw ValidationException::withMessages(['category' => 'General is the default category and cannot be deleted.']);
            }
        });
    }
}
