<?php

namespace App\Models;

use App\Enums\AnswerResponse;
use Database\Factories\AnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['equipment_id', 'question_id', 'response', 'language_chosen'])]
class Answer extends Model
{
    /** @use HasFactory<AnswerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'response' => AnswerResponse::class,
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function defects(): HasMany
    {
        return $this->hasMany(Defect::class);
    }
}
