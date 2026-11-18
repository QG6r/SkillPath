<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Submission;
use App\Models\User;

/**
 * Decides which modules of a course a student may open.
 *
 * The rule: module 1 is open once the student has joined the course. Module N opens when the
 * latest submission for the final work of module N-1 has been marked "correct" by a teacher.
 * A module without a final work has nothing to pass, so it counts as passed and does not block the next one.
 */
class CourseProgress
{
    public const LOCKED = 'locked';
    public const OPEN = 'open';              // reachable, nothing handed in yet
    public const PENDING = 'pending';        // handed in, waiting for the teacher
    public const NEEDS_FIX = 'needs_fix';    // the teacher marked it incorrect; the student can hand in again
    public const PASSED = 'passed';

    /**
     * State of every module of the course for this student, in course order.
     *
     * @return array<int, string> module id => state
     */
    public function moduleStates(User $student, Course $course): array
    {
        $modules = $course->modules()->with('task')->get();

        $taskIds = $modules->map(fn ($module) => $module->task?->id)->filter()->values();

        // Ordered by id, so for every task the last (latest) submission wins in keyBy().
        $latest = Submission::query()
            ->where('user_id', $student->id)
            ->whereIn('task_id', $taskIds)
            ->orderBy('id')
            ->get()
            ->keyBy('task_id');

        $states = [];
        $previousPassed = true;

        foreach ($modules as $module) {
            if (! $previousPassed) {
                $states[$module->id] = self::LOCKED;

                continue;
            }

            if ($module->task === null) {
                $state = self::PASSED;
            } else {
                $submission = $latest->get($module->task->id);

                $state = match ($submission?->status) {
                    null => self::OPEN,
                    Submission::STATUS_CORRECT => self::PASSED,
                    Submission::STATUS_PENDING => self::PENDING,
                    default => self::NEEDS_FIX,
                };
            }

            $states[$module->id] = $state;
            $previousPassed = $state === self::PASSED;
        }

        return $states;
    }

    /** Share of modules passed, 0-100. A course without modules has no progress. */
    public function percent(User $student, Course $course): int
    {
        $states = $this->moduleStates($student, $course);

        if ($states === []) {
            return 0;
        }

        $passed = count(array_filter($states, fn ($state) => $state === self::PASSED));

        return (int) round($passed / count($states) * 100);
    }

    public function canOpen(string $state): bool
    {
        return $state !== self::LOCKED;
    }

    public function canSubmit(string $state): bool
    {
        return $state === self::OPEN || $state === self::NEEDS_FIX;
    }

    public function isEnrolled(User $student, Course $course): bool
    {
        return $course->students()->whereKey($student->id)->exists();
    }

    /** Stores the progress on the enrollment row, so it can be listed without recalculating. */
    public function syncEnrollment(User $student, Course $course): void
    {
        $percent = $this->percent($student, $course);

        $course->students()->updateExistingPivot($student->id, [
            'progress' => $percent,
            'completed_at' => $percent === 100 ? now() : null,
        ]);
    }
}
