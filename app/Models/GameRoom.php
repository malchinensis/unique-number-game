<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'creator_id',
        'min_number',
        'max_number',
        'min_players',
        'max_players',
        'status',
        'round',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(GameParticipant::class);
    }

    public function canStart(): bool
    {
        $participantCount = $this->participants()->count();
        return $participantCount >= $this->min_players && $participantCount <= $this->max_players;
    }

    public function allPlayersSubmitted(): bool
    {
        $totalParticipants = $this->participants()->count();
        $submittedParticipants = $this->participants()
            ->whereNotNull('selected_number')
            ->count();

        return $totalParticipants > 0 && $totalParticipants === $submittedParticipants;
    }
}
