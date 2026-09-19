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
