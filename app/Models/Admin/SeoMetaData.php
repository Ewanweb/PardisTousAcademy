<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class SeoMetaData extends Model
{
    protected $table = 'seo_metadata';
    protected $guarded = [];

    public function seoable()
    {
        return $this->morphTo();
    }
}
