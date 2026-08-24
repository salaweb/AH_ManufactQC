<?php

namespace App\Models;

use App\Enums\EquipmentStatus;
use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'order_fabrication_id', 'serie_number', 'observations', 'status', 'checked_at'])]
class Equipment extends Model
{
    /** @use HasFactory<EquipmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => EquipmentStatus::class,
            'checked_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function orderFabrication(): BelongsTo
    {
        return $this->belongsTo(OrderFabrication::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function defects(): HasMany
    {
        return $this->hasMany(Defect::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }
}
