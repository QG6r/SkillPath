<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data: one admin, one teacher, one student (all with the password "password")
 * and a course with three modules, each with two topics and a final work.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->user('Ada Admin', 'admin@skillpath.test', User::ROLE_ADMIN);
        $teacher = $this->user('Tom Teacher', 'teacher@skillpath.test', User::ROLE_TEACHER);
        $this->user('Sara Student', 'student@skillpath.test', User::ROLE_STUDENT);

        $course = Course::firstOrCreate(
            ['slug' => 'java-basics'],
            [
                'title' => 'Java Basics',
                'description' => 'A first course in Java: syntax, classes and a small console project. '
                    .'Pass each module by sending a GitHub link with your work; the next module opens when the teacher accepts it.',
                'teacher_id' => $teacher->id,
                'level' => 'beginner',
                'is_published' => true,
            ]
        );

        // Do not rebuild the content if the course was already seeded (and maybe edited by an admin).
        if ($course->modules()->exists()) {
            return;
        }

        $modules = [
            [
                'title' => 'Getting started',
                'description' => 'Install the tools and run your first program.',
                'topics' => [
                    ['What is Java?', 'Java is a statically typed language that runs on the JVM. Source files are compiled to bytecode.'],
                    ['Your first program', 'Create a class with a main method and print a line with System.out.println.'],
                ],
                'task' => ['Hello, GitHub', 'Create a repository with a Hello World program and a README that explains how to run it. Send the link to the repository.'],
            ],
            [
                'title' => 'Classes and objects',
                'description' => 'Model real things with classes.',
                'topics' => [
                    ['Fields and methods', 'A class groups data (fields) and behaviour (methods). Objects are instances of a class.'],
                    ['Encapsulation', 'Keep fields private and expose behaviour through methods.'],
                ],
                'task' => ['Library model', 'Write classes Book and Reader with a method that lets a reader borrow a book. Push to GitHub and send the link.'],
            ],
            [
                'title' => 'A console project',
                'description' => 'Put it all together.',
                'topics' => [
                    ['Reading input', 'Use Scanner to read what the user types and react to it.'],
                    ['Structuring a project', 'Split code into packages: model, service, and the entry point.'],
                ],
                'task' => ['Console tracker', 'Build a small console application (for example an expense tracker) with a menu. Push to GitHub, add a README, and send the link.'],
            ],
        ];

        foreach ($modules as $index => $data) {
            $module = $course->modules()->create([
                'title' => $data['title'],
                'description' => $data['description'],
                'order' => $index + 1,
            ]);

            foreach ($data['topics'] as $position => [$title, $content]) {
                $module->topics()->create([
                    'title' => $title,
                    'content' => $content,
                    'order' => $position + 1,
                ]);
            }

            $module->task()->create([
                'title' => $data['task'][0],
                'instructions' => $data['task'][1],
            ]);
        }
    }

    private function user(string $name, string $email, string $role): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'role' => $role,
                'email_verified_at' => now(),
            ]
        );
    }
}
