<?php

namespace App\Traits;

use App\Models\Admin\SeoMetaData;

trait HasSeo
{
    public function seo()
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }

    /**
     * متد کمکی برای آپدیت یا ایجاد سئو
     */
    public function updateSeo(array $data)
    {
        $this->seo()->updateOrCreate(
            ['id' => $this->seo?->id],
            $data
        );
    }
}
