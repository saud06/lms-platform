<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Web Development', 'Data Science', 'Mobile Development', 'DevOps', 'UI/UX Design', 'Cybersecurity', 'Cloud Computing', 'AI/ML'];
        $levels = [Course::LEVEL_BEGINNER, Course::LEVEL_INTERMEDIATE, Course::LEVEL_ADVANCED];
        $statuses = [Course::STATUS_DRAFT, Course::STATUS_PUBLISHED, Course::STATUS_ARCHIVED];

        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraphs(3, true),
            'short_description' => fake()->sentence(10),
            'instructor_id' => User::where('role', User::ROLE_INSTRUCTOR)->inRandomOrder()->first()->id,
            'category' => fake()->randomElement($categories),
            'level' => fake()->randomElement($levels),
            'duration_hours' => fake()->numberBetween(10, 50),
            'price' => fake()->randomFloat(2, 49.99, 299.99),
            'status' => fake()->randomElement($statuses),
            'max_students' => fake()->optional(0.7)->numberBetween(20, 200),
            'requirements' => fake()->randomElements([
                'Basic programming knowledge',
                'Computer with internet connection',
                'Willingness to learn',
                'High school mathematics',
                'Basic English proficiency',
                'No prior experience required'
            ], fake()->numberBetween(2, 4)),
            'learning_outcomes' => fake()->randomElements([
                'Build real-world projects',
                'Understand core concepts',
                'Apply best practices',
                'Solve complex problems',
                'Work with modern tools',
                'Deploy applications',
                'Debug and troubleshoot',
                'Write clean code'
            ], fake()->numberBetween(3, 6)),
            'is_featured' => fake()->boolean(20), // 20% chance of being featured
        ];
    }

    /**
     * Indicate that the course is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Course::STATUS_PUBLISHED,
        ]);
    }

    /**
     * Indicate that the course is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the course is for beginners.
     */
    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => Course::LEVEL_BEGINNER,
        ]);
    }
}
