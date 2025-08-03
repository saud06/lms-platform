<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = User::where('role', User::ROLE_STUDENT)->get();
        $courses = Course::where('status', Course::STATUS_PUBLISHED)->get();

        foreach ($students as $student) {
            // Enroll each student in 2-5 random courses to ensure more data
            $coursesToEnroll = $courses->random(rand(2, 5));
            
            foreach ($coursesToEnroll as $course) {
                $enrollment = Enrollment::create([
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                    // Spread enrollments across the last 6 months for trend charts
                    'enrolled_at' => now()->subDays(rand(1, 180)),
                    'progress' => rand(0, 100),
                ]);

                // Some enrollments are completed
                if ($enrollment->progress >= 90) {
                    $enrollment->update([
                        // Completed sometime within the last 2 months
                        'completed_at' => now()->subDays(rand(1, 60)),
                        'progress' => 100,
                        'rating' => rand(3, 5),
                        'review' => $this->getRandomReview(),
                        'certificate_issued' => true,
                    ]);
                }
            }
        }
    }

    private function getRandomReview()
    {
        $reviews = [
            'Excellent course! Very well structured and easy to follow.',
            'Great instructor and comprehensive content. Highly recommended!',
            'Good course overall, learned a lot of practical skills.',
            'Well-paced course with hands-on projects. Really enjoyed it.',
            'Clear explanations and good examples. Worth the investment.',
            'Fantastic course! The instructor explains complex topics very clearly.',
            'Very practical course with real-world applications.',
            'Good content but could use more interactive elements.',
            'Solid course foundation with good progression through topics.',
            'Excellent value for money. Would take more courses from this instructor.',
        ];

        return $reviews[array_rand($reviews)];
    }
}
