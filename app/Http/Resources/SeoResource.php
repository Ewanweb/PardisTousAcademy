<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class SeoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // دسترسی به مدل اصلی (مثلاً Course) برای مقادیر پیش‌فرض
        $model = $this->resource->seoable;

        return [
            'title' => $this->meta_title ?? $model->title, // فال‌بک به تایتل اصلی
            'description' => $this->meta_description ?? Str::limit($model->description, 160),
            'canonical' => $this->canonical_url ?? url()->current(),
            'robots' => [
                'index' => !$this->noindex,
                'follow' => !$this->nofollow,
            ],
            'open_graph' => [
                'title' => $this->og_title ?? ($this->meta_title ?? $model->title),
                'description' => $this->og_description ?? ($this->meta_description ?? Str::limit($model->description, 200)),
                'image' => $this->og_image ?? $model->image_url, // عکس اصلی دوره
            ],
            // تولید اتوماتیک اسکیما برای گوگل
            'schema' => $this->generateSchema($model),
        ];
    }

    private function generateSchema($model)
    {
        // اگر اسکیما دستی وارد شده بود همان را بده، وگرنه اتوماتیک بساز
        if (!empty($this->schema_markup)) {
            return $this->schema_markup;
        }

        // چک میکنیم اگر مدل "دوره" بود، اسکیمای Course بسازیم
        if ($model instanceof \App\Models\Course) {
            return [
                '@context' => 'https://schema.org',
                '@type' => 'Course',
                'name' => $model->title,
                'description' => strip_tags($model->description),
                'provider' => [
                    '@type' => 'Organization',
                    'name' => 'Pardis Academy',
                    'sameAs' => config('app.url')
                ],
                'offers' => [
                    '@type' => 'Offer',
                    'category' => 'Paid',
                    'priceCurrency' => 'IRR',
                    'price' => $model->price,
                ],
                'hasCourseInstance' => [
                    '@type' => 'CourseInstance',
                    'courseMode' => 'online',
                    'instructor' => [
                        '@type' => 'Person',
                        'name' => $model->instructor->name
                    ]
                ]
            ];
        }

        return null;
    }
}
