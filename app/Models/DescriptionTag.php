<?php

namespace App\Models;

use Database\Factories\DescriptionTagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class DescriptionTag extends Model
{
    /** @use HasFactory<DescriptionTagFactory> */
    use HasFactory;

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_description_tag');
    }
}
