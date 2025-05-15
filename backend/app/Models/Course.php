<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'short_description',
        'instructor_id',
        'category',
        'level',
        'duration_hours',
        'price',
        'thumbnail',
        'status',
        'max_students',
        'requirements',
        'learning_outcomes',
        'is_featured',
    ];

    protected $casts = [
        'requirements' => 'array',
        'learning_outcomes' => 'array',
        'price' => 'decimal:2',
        'duration_hours' => 'integer',
        'max_students' => 'integer',
        'is_featured' => 'boolean',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    const LEVEL_BEGINNER = 'beginner';
    const LEVEL_INTERMEDIATE = 'intermediate';
    const LEVEL_ADVANCED = 'advanced';

    /**
     * Course instructor
     */
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Course lessons
     */
    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    /**
     * Course enrollments
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Enrolled students
     */
    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments')
                    ->withPivot('enrolled_at', 'completed_at', 'progress')
                    ->withTimestamps();
    }

    /**
     * Course quizzes
     */
    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * Course certificates
     */
    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get total enrolled students count
     */
    public function getEnrolledCountAttribute()
    {
        return $this->enrollments()->count();
    }

    /**
     * Get course completion rate
     */
    public function getCompletionRateAttribute()
    {
        $totalEnrollments = $this->enrollments()->count();
        if ($totalEnrollments === 0) return 0;
        
        $completedEnrollments = $this->enrollments()->whereNotNull('completed_at')->count();
        return round(($completedEnrollments / $totalEnrollments) * 100, 2);
    }

    /**
     * Get average rating
     */
    public function getAverageRatingAttribute()
    {
        return $this->enrollments()->whereNotNull('rating')->avg('rating') ?? 0;
    }

    /**
     * Check if course is published
     */
    public function isPublished()
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Check if course has available spots
     */
    public function hasAvailableSpots()
    {
        if (!$this->max_students) return true;
        return $this->enrolled_count < $this->max_students;
    }
}
