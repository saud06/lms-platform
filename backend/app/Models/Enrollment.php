<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'enrolled_at',
        'completed_at',
        'progress',
        'rating',
        'review',
        'certificate_issued',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress' => 'integer',
        'rating' => 'integer',
        'certificate_issued' => 'boolean',
    ];

    /**
     * Student enrolled in the course
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * User enrolled in the course (alias for student)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Course being enrolled in
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Check if enrollment is completed
     */
    public function isCompleted()
    {
        return !is_null($this->completed_at);
    }

    /**
     * Mark enrollment as completed
     */
    public function markAsCompleted()
    {
        $this->update([
            'completed_at' => now(),
            'progress' => 100,
        ]);
    }

    /**
     * Update progress
     */
    public function updateProgress($progress)
    {
        $this->update(['progress' => min(100, max(0, $progress))]);
        
        if ($progress >= 100 && !$this->completed_at) {
            $this->markAsCompleted();
        }
    }
}
