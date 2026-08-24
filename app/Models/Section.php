<?php

namespace App\Models;

use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'order'])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }
}
