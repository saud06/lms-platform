<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'content',
        'video_url',
        'duration_minutes',
        'order',
        'is_free',
        'resources',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'order' => 'integer',
        'is_free' => 'boolean',
        'resources' => 'array',
    ];

    /**
     * Course this lesson belongs to
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Lesson progress records
     */
    public function progress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * Check if lesson is completed by user
     */
    public function isCompletedBy($userId)
    {
        return $this->progress()->where('user_id', $userId)->where('completed', true)->exists();
    }
}
