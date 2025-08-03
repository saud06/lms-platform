<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'time_limit',
        'max_attempts',
        'passing_score',
        'is_active',
        'order',
    ];

    protected $casts = [
        'time_limit' => 'integer',
        'max_attempts' => 'integer',
        'passing_score' => 'integer',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Course this quiz belongs to
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Quiz questions
     */
    public function questions()
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    /**
     * Quiz attempts
     */
    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get user's attempts for this quiz
     */
    public function userAttempts($userId)
    {
        return $this->attempts()->where('user_id', $userId);
    }

    /**
     * Check if user can take this quiz
     */
    public function canUserTake($userId)
    {
        if (!$this->is_active) return false;
        
        $attemptCount = $this->userAttempts($userId)->count();
        return $this->max_attempts === 0 || $attemptCount < $this->max_attempts;
    }

    /**
     * Get user's best score
     */
    public function getUserBestScore($userId)
    {
        return $this->userAttempts($userId)->max('score') ?? 0;
    }
}
