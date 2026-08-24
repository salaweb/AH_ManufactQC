<?php

namespace App\Models;

use Database\Factories\DefectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['equipment_id', 'answer_id', 'tipo', 'observation', 'responsibility', 'actions'])]
class Defect extends Model
{
    /** @use HasFactory<DefectFactory> */
    use HasFactory;

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function answer(): BelongsTo
    {
        return $this->belongsTo(Answer::class);
    }
}
