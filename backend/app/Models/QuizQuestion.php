<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'question',
        'type',
        'options',
        'correct_answer',
        'explanation',
        'points',
        'order',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
        'points' => 'integer',
        'order' => 'integer',
    ];

    const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    const TYPE_TRUE_FALSE = 'true_false';
    const TYPE_SHORT_ANSWER = 'short_answer';

    /**
     * Quiz this question belongs to
     */
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Question answers from attempts
     */
    public function answers()
    {
        return $this->hasMany(QuizAnswer::class);
    }

    /**
     * Check if answer is correct
     */
    public function isCorrectAnswer($answer)
    {
        if ($this->type === self::TYPE_MULTIPLE_CHOICE) {
            // Normalize provided answer: can be array [index] or scalar index or option string
            $provided = is_array($answer) ? ($answer[0] ?? null) : $answer;
            // Normalize correct answer: can be [index] or [optionString]
            $correct = $this->correct_answer[0] ?? null;

            // If both are numeric indexes, compare indexes
            if (is_numeric($provided) && is_numeric($correct)) {
                return intval($provided) === intval($correct);
            }
            // If provided is index and correct is option string, compare option value at that index
            if (is_numeric($provided) && is_string($correct)) {
                $idx = intval($provided);
                return isset($this->options[$idx]) && $this->options[$idx] === $correct;
            }
            // If provided is string value, compare directly to correct value
            if (is_string($provided)) {
                return in_array($provided, $this->correct_answer, true);
            }
            return false;
        }
        
        if ($this->type === self::TYPE_TRUE_FALSE) {
            $provided = is_array($answer) ? ($answer[0] ?? null) : $answer;
            return $provided === ($this->correct_answer[0] ?? null);
        }
        
        // For short answer, we'll do a simple string comparison
        if ($this->type === self::TYPE_SHORT_ANSWER) {
            $provided = is_array($answer) ? ($answer[0] ?? '') : ($answer ?? '');
            return strtolower(trim((string)$provided)) === strtolower(trim((string)($this->correct_answer[0] ?? '')));
        }
        
        return false;
    }
}
