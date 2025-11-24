<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->name, // در فرانت معمولا title استفاده می‌شود
            'slug' => $this->slug,
            'image' => $this->image,
            'is_active' => (bool) $this->is_active,

            // نمایش والد (اگر وجود داشته باشد)
            'parent_id' => $this->parent_id,

            // نمایش فرزندان (به صورت بازگشتی - Recursive)
            // این خط جادویی باعث می‌شود ساختار درختی کامل شکل بگیرد
            'children' => CategoryResource::collection($this->whenLoaded('children')),

            // نمایش تعداد دوره‌های داخل این دسته
            'courses_count' => $this->whenCounted('courses'),
            'creator' => $this->creator ? $this->creator->name : 'سیستم',

            // دیتای سئو (از مرحله قبل)
            'seo' => new SeoResource($this->whenLoaded('seo')),
        ];
    }
}
