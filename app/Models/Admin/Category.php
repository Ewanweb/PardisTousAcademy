<?php

namespace App\Models\Admin;

use App\Traits\HasSeo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\Admin\CategoryFactory> */
    use HasFactory, HasSeo;

    protected $fillable = ['name', 'slug', 'image','parent_id', 'is_active'];

    // رابطه با فرزندان (زیرمجموعه‌ها)
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // رابطه با والد
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // رابطه با دوره‌ها
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
