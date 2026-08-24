<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['number', 'family', 'description', 'observations'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    public function orderFabrications(): HasMany
    {
        return $this->hasMany(OrderFabrication::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }
}
