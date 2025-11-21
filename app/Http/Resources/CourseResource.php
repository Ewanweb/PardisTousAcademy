<?php

namespace App\Http\Resources;

use App\Models\Admin\SeoMetaData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description, // می‌توان خلاصه کرد Str::limit
            'price' => $this->price,
            'price_formatted' => number_format($this->price) . ' تومان',
            'status' => $this->status,
            'image' => $this->image,

            // روابط (فقط وقتی لود شده باشند نمایش داده می‌شوند)
            'instructor' => new UserResource($this->whenLoaded('instructor')),
            'category' => new CategoryResource($this->whenLoaded('category')),

            'created_at' => $this->created_at->toIso8601String(),

            // سئو
            'seo' => new SeoResource(
            // اگر رابطه لود شده بود و وجود داشت -> خودش را بده
            // اگر نبود -> یک آبجکت خالی سئو بساز که به خودِ این دوره وصل است
                $this->seo ?? new SeoMetadata(['seoable' => $this->resource])
            ),
        ];
    }
}
