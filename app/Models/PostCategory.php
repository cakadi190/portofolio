<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Traits\Models\AutoGenerateSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PostCategory extends Model
{
    use AutoGenerateSlug, RecordsActivity;

    protected $fillable = ['name', 'slug', 'color'];

    /**
     * @return BelongsToMany<Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_category_post');
    }
}
