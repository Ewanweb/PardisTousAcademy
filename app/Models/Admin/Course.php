<?php

namespace App\Models\Admin;

use App\Models\User;
use App\Traits\HasSeo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    /** @use HasFactory<\Database\Factories\Admin\CourseFactory> */
    use HasFactory, HasSeo, SoftDeletes;

    protected $fillable = [
        'instructor_id', 'image','category_id', 'title', 'slug', 'description', 'price', 'status'
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Scope برای گرفتن فقط دوره‌های منتشر شده (برای تمیزی کد در کنترلر)
    public function scopePublished(Builder $query)
    {
        return $query->where('status', 'published');
    }
}
