# ThesisCap

Thesis and Capstone Milestone Monitoring System. Built with **Laravel 12** (PHP 8.2 or newer, so it runs on XAMPP). It follows a thesis or capstone from the proposal stage to the final submission: milestones, tasks, files with version history, instructor feedback and approvals, reminders and reports.

No npm or Vite build is needed. The styling is plain CSS in `public/css/app.css`.

## Requirements
- PHP 8.2 or newer with the `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `fileinfo` extensions enabled in `php.ini` (or use MySQL)
- Composer 2

## Setup
Linux/macOS:
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```
Windows (XAMPP, Command Prompt inside the project folder):
```bat
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```
`database/database.sqlite` is already included. Open http://localhost:8000. (`composer run setup` runs the same steps.)

### Using MySQL instead of SQLite
In `.env` set `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, create the empty database, then run `php artisan migrate --seed`.

## Demo accounts (password: `password`)
| Role | Email |
|---|---|
| Student (group leader) | student@thesiscap.test |
| Student | ana@thesiscap.test, mark@thesiscap.test, carla@thesiscap.test, paolo@thesiscap.test |
| Instructor | instructor@thesiscap.test |
| Adviser | adviser@thesiscap.test |
| Project coordinator | coordinator@thesiscap.test |

The demo files are small text placeholders. Real uploads accept PDF, Word, PowerPoint, Excel, ZIP, images and text up to 20 MB and are stored privately in `storage/app/private/documents`.

## Reminders
```bash
php artisan thesiscap:send-reminders      # run once
php artisan schedule:work                 # run the daily 7:00 AM schedule locally
```
In production add the usual cron entry: `* * * * * php /path/to/artisan schedule:run`.

## Features and where they live
| Feature | Where |
|---|---|
| Registration and login | `/register`, `/login` (`AuthController`) |
| Student and instructor profiles | `/profile` |
| Project creation (with standard thesis phases) | `/projects/create` |
| Groups and members | `/groups` |
| Phases and milestones, submit, approve, request revision | `/projects/{id}` |
| Tasks, assignment, deadlines | `/tasks` |
| Progress updates and individual task history | `/tasks/{id}` |
| Files, folders, favorites, trash, search | `/documents` |
| Revisions and version history | `/documents/{id}` |
| Instructor feedback and approval | milestone and file review, `/feedback` |
| Project timeline | `/timeline` |
| Dashboards (student and instructor) | `/dashboard` |
| Notifications and reminders | `/notifications` |
| Approve instructor/adviser accounts (coordinator) | `/users` |
| Progress and completion reports (print, PDF, CSV) | `/reports` |

## Roles
Students can register themselves. **Instructor and adviser registrations stay locked until the project coordinator approves them** (Users page). Only approved instructors and advisers can be assigned to a group. The coordinator account is created by the seeder only, so change its password before real use.

- **Student**: sees their own groups. The group leader can create projects, add members and assign tasks. Members update their own tasks, upload files and submit milestones.
- **Instructor / Adviser**: sees the groups assigned to them, reviews milestones and files, comments, and reads reports.
- **Project coordinator**: sees everything.

## Look and feel
`public/css/app.css` holds the base components and `public/css/theme.css` adds the glass theme, animations and dashboard hero. `public/js/theme.js` runs the number count-up. There are three dashboards: student, instructor/adviser and coordinator (`resources/views/dashboard`).

## Design reference
The original screens are in `public/design/` as PNG files. The layout, colors and components in `resources/views` follow them.

## Upgrading an existing install
If you already ran the migrations, just run `php artisan migrate` (not `migrate:fresh`). It adds the `approved` column and keeps your data.
