<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_id',
        'started_at',
        'completed_at',
        'score',
        'total_questions',
        'correct_answers',
        'time_taken',
        'passed',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'score' => 'integer',
        'total_questions' => 'integer',
        'correct_answers' => 'integer',
        'time_taken' => 'integer',
        'passed' => 'boolean',
    ];

    /**
     * User who took the quiz
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Quiz that was attempted
     */
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Answers given in this attempt
     */
    public function answers()
    {
        return $this->hasMany(QuizAnswer::class);
    }

    /**
     * Check if attempt is completed
     */
    public function isCompleted()
    {
        return !is_null($this->completed_at);
    }

    /**
     * Calculate and update score
     */
    public function calculateScore()
    {
        $totalPoints = $this->quiz->questions()->sum('points');
        $earnedPoints = 0;

        foreach ($this->answers as $answer) {
            if ($answer->question->isCorrectAnswer($answer->answer)) {
                $earnedPoints += $answer->question->points;
            }
        }

        $score = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100) : 0;
        $passed = $score >= $this->quiz->passing_score;

        $this->update([
            'score' => $score,
            'correct_answers' => $this->answers()->whereHas('question', function($q) {
                $q->whereRaw('quiz_answers.answer = JSON_EXTRACT(correct_answer, "$[0]")');
            })->count(),
            'passed' => $passed,
        ]);

        return $score;
    }
}
