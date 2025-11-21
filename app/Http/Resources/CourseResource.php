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
            'price_formatted' => number_format($this->price) . ' تومان',
            'status' => $this->status,
            'category' => new CategoryResource($this->whenLoaded('category')), // لود کردن مشروط
            'instructor' => [
                'name' => $this->instructor->name,
                'avatar' => '...', // لینک آواتار
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'seo' => new SeoResource($this->seo ?? new SeoMetaData(['seoable' => $this])),
        ];
    }
}
