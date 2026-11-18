# SkillPath

A small learning platform. An admin builds courses out of modules, a student joins a course and works through the modules
one by one, and a teacher reviews each module's final work. A module opens only after the previous one has been accepted.

Built with Laravel 12, Inertia 2 (Vue 3), Tailwind 3 and SQLite.

## Roles

| Role | What they do |
| --- | --- |
| **Admin** | Creates courses, modules, topics (text and a YouTube/Vimeo link) and one final work per module. Assigns a teacher to each course. Creates accounts and changes roles. |
| **Student** | Picks a course from the catalog (free, no purchase), reads the topics, and submits the final work as a GitHub link plus a message to the teacher. |
| **Teacher** | Sees the submissions of the courses assigned to them, marks each one correct or incorrect and writes feedback. |

Registration always creates a student. Teachers and admins are created by an admin.

## How a course works

1. A student joins a course. Module 1 opens.
2. The student reads the topics and submits the final work (a `https://github.com/<user>/<repo>` link and a message).
3. The teacher marks it **correct** or **incorrect**. Feedback is required for incorrect.
4. Correct: the next module opens and the course progress grows. Incorrect: the student fixes the work and submits again.
   There is no limit on attempts, and the latest attempt decides.

A module can be in one of five states for a student: `locked`, `open`, `pending` (waiting for the teacher),
`needs_fix` (marked incorrect) and `passed`. A module without a final work counts as passed. The rule lives in
`app/Services/CourseProgress.php`.

## Run with Docker

```bash
docker compose up --build
```

Open http://localhost:8000. The container creates the database, runs the migrations and loads the demo data.
The data stays in a Docker volume between restarts; `docker compose down -v` wipes it.

Demo accounts (password for all: `password`):

| Role | Email |
| --- | --- |
| Admin | `admin@skillpath.test` |
| Teacher | `teacher@skillpath.test` |
| Student | `student@skillpath.test` |

The demo course "Java Basics" has 3 modules, each with two topics and a final work, and is assigned to the demo teacher.

Try it: log in as the student, join the course and submit module 1; log in as the teacher, mark it correct; the student
now sees module 2 open.

## Run locally

Requires PHP 8.3, Composer and Node 22.

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run build        # or `npm run dev` while working on the front end
php artisan serve
```

If you already have a database from an earlier version of this project, recreate it with
`php artisan migrate:fresh --seed`, because the migrations were rewritten.

## Tests

```bash
npm run build        # feature tests render pages, so the Vite manifest must exist
php artisan test
```

The tests cover the unlock rule and every state of a module, GitHub link validation, resubmission, teacher ownership of
courses, role access, the admin screens, the video link conversion and the demo seeder. CI (`.github/workflows/ci.yml`)
runs them and also builds the Docker image and checks that the site starts with the demo data.

## Data model

```
users (role: student | teacher | admin)
courses (teacher_id -> users, is_published)
  └─ modules (order)
       ├─ topics (text, video_url, order)
       └─ task (one final work per module)
            └─ submissions (user_id, github_url, message, status, feedback, reviewed_by)
course_user (student enrollment, progress, completed_at)
```

`categories` still exist in the database but are not used in the interface.

## Project layout

- `app/Http/Controllers/{Student,Teacher,Admin}` — one folder per role; routes are in `routes/web.php`.
- `app/Http/Middleware/RoleMiddleware.php` — `role:student|teacher|admin`.
- `app/Services/CourseProgress.php` — module states, progress and the unlock rule.
- `resources/js/Pages/{Student,Teacher,Admin}` — Inertia pages.
- `database/seeders/DemoSeeder.php` — the demo accounts and course (safe to run twice).

## Limits of this prototype

- Videos are embedded from a YouTube or Vimeo link; there is no file upload.
- Email verification is off, so a new student can start right away.
- A teacher sees only the submissions of courses assigned to them.
- SQLite is used for simplicity; the demo is not meant for production traffic.
