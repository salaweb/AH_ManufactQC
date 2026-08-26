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

    public function statusFor(bool $finished): EquipmentStatus
    {
        // Once finished, "amb defectes" is a permanent record of the review: it stays
        // true even if a defect answer is later flipped back to yes, because a defect
        // genuinely was found during this inspection.
        if ($finished) {
            return $this->defects()->exists() ? EquipmentStatus::OkWithDefects : EquipmentStatus::Ok;
        }

        // While still being reviewed, "amb defectes" tracks the live state: only
        // questions *currently* answered "defect" count, so the badge updates the
        // moment a question is answered differently.
        $hasCurrentDefectAnswer = $this->answers()->where('response', 'defect')->exists();

        return $hasCurrentDefectAnswer ? EquipmentStatus::PendingWithDefects : EquipmentStatus::Pending;
    }

    public function isFullyAnswered(): bool
    {
        $sectionIds = $this->project->sections->pluck('id');
        $requiredQuestionIds = Question::whereIn('section_id', $sectionIds)->where('is_required', true)->pluck('id');
        $answeredQuestionIds = $this->answers()->pluck('question_id');

        return $requiredQuestionIds->diff($answeredQuestionIds)->isEmpty();
    }

    public function hasCurrentDefectAnswer(): bool
    {
        return $this->answers()->where('response', 'defect')->exists();
    }

    /**
     * A review can only be finalized once every required question is answered and
     * none of them currently reads "defect" — a defect must be resolved (answered
     * yes/no) before the review can be finished, even if it's already documented.
     */
    public function isProperlyFinished(): bool
    {
        return $this->isFullyAnswered() && ! $this->hasCurrentDefectAnswer();
    }

    /**
     * Recompute `status` (and, if the equipment is no longer properly finished, clear
     * `checked_at` too) from the current answers/defects. Safe to call after any
     * mutation that could affect either: an answer being created, changed or deleted,
     * or a defect being created or deleted.
     */
    public function refreshStatus(): void
    {
        if ($this->checked_at !== null && ! $this->isProperlyFinished()) {
            $this->checked_at = null;
        }

        $this->status = $this->statusFor($this->checked_at !== null);
        $this->save();
    }
}
