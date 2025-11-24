<?php

namespace App\Models\Admin;

use App\Models\User;
use App\Traits\HasSeo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\Admin\CategoryFactory> */
    use HasFactory, HasSeo;

    protected $fillable = ['name', 'slug', 'image','parent_id', 'is_active', 'created_by'];

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAllChildrenIds()
    {
        // خودم + تمام بچه‌هایم + بچه‌های بچه‌هایم (بازگشتی)
        $ids = collect([$this->id]);

        foreach ($this->children as $child) {
            $ids = $ids->merge($child->getAllChildrenIds());
        }

        return $ids;
    }
}
