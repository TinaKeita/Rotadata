# Rotadata

Costume inventory for folk dance and music groups. Teachers keep track of what the group owns, who has which costume, and whether everyone is ready for the next concert. Students take a costume by scanning the QR label sewn into it.

## What it does

**For teachers**
- **Costumes:** add each costume once with a quantity. Rotadata gives every item its own code (e.g. `KRE-03`) and a printable QR label.
- **Students:** add a whole group at once by name and email. New students get a sign-in invite by email; people who already have an account get an invite to accept.
- **Sets:** sort students and costumes into sets such as "Girls" and "Boys", so each student is only asked for the costumes they need.
- **Concerts:** plan a concert, tick who is performing and add extra costumes for soloists. The dashboard shows who is still missing what.
- **Hand out and take back:** teachers can do this without scanning, and every item keeps a full history.
- **Search:** look up any item by its code, or any student by name.
- **Season report:** a printable report for the school year (1 September – 31 August), showing what is still out and how costumes were used.
- **Several groups:** a teacher can run more than one group, switch between them, and hand a group over to another teacher.
- **Undo window:** a deleted group or removed student can be restored for 30 days.

**For students**
- Scan a QR label to take that costume, or take it over from a classmate.
- See their own costumes, history and concert readiness, and return items from their list.

## Accounts and access

**Anyone can register as a teacher, on purpose.** Rotadata is for any dance or music group, including hobby groups and clubs with no school behind them, so there is no invite code, school domain or approval step. Registering gives you your own empty group and nothing else. You get no access to other groups, students or costumes.

Because sign-up is open, the things a teacher account can do to *other* people are limited:

- **New students join by setting their own password.** Adding a new email creates the account and sends a temporary password, valid for 7 days. Until they sign in and choose their own password, they are only *invited*: they can't be handed costumes, don't count towards concerts, and get no other emails from Rotadata. An invite that runs out can be resent from the student's profile.
- **Existing accounts accept or decline.** If the email already belongs to a Rotadata account (another group's student, or another teacher), nobody is added straight away. The person gets an invite on their dashboard and by email, and joins only if they press **Accept**. Until then the teacher can't see their account. Unanswered invites expire after 7 days, and the teacher can cancel them from the members page.
- **Daily limit.** A teacher can send at most 60 invites in 24 hours, counting new accounts and invites to existing ones. That covers a whole group in one go but stops anyone using Rotadata to send email to lots of strangers.
- **Handover search shows no full emails.** When handing a group to another teacher, you search by name or by their exact email address. Results show a masked address (`j***@example.com`), so teachers' contact details can't be collected.
- **Passwords are only changed by their owner,** through the "Forgot password" email link. Teachers never see or set a student's password.
- **Takeovers are visible.** When a student takes over a costume from a classmate, the teacher sees it on the dashboard and the previous holder gets an email, so a takeover that wasn't agreed can be undone.

## Built with

| Part | Tools |
|---|---|
| Back end | PHP 8.3, [Laravel 12](https://laravel.com) |
| Sign-in | Scaffolded with [Laravel Breeze](https://laravel.com/docs/starter-kits), then customised: forced password change on first sign-in, students invited by their teacher, password reset by email link |
| Roles | [spatie/laravel-permission](https://spatie.be/docs/laravel-permission): `admin` (teacher) and `member` (student) |
| QR codes | [simplesoftwareio/simple-qrcode](https://github.com/SimpleSoftwareIO/simple-qrcode) |
| Email | [Resend](https://resend.com) in production; written to the log locally |
| Front end | Blade templates, [Tailwind CSS 3](https://tailwindcss.com), [Alpine.js](https://alpinejs.dev), built with [Vite](https://vitejs.dev) |
| Tests | [Pest](https://pestphp.com) / PHPUnit |
| Package managers | Composer (PHP) and npm (JavaScript) |

The interface follows the device's light or dark mode automatically.

## Requirements

- PHP 8.3 or newer, with the `gd` extension (needed for QR images)
- Composer 2, a recent version (run `composer self-update`)
- Node.js 20.19 or newer, and npm
- SQLite (the default) or MySQL. Laragon provides all of these.

## Setup

```bash
git clone <repository-url> rotadata
cd rotadata
composer setup
```

`composer setup` does all of the following:
- installs the packages
- creates `.env` from `.env.example` and generates an app key
- creates the database tables and the `admin`/`member` roles
- links storage for costume photos
- builds the front end

Then open the site:
- **With Laragon:** open the project's Laragon URL (e.g. `http://rotadata.test`).
- **Without Laragon:** run `composer dev`. It starts the web server, the Vite dev server and a live log viewer together.

Go to **Create a teacher account** to register. Registering creates your first group. There is no default account.

## Configuration

Set these in `.env`:

| Setting | Why it matters |
|---|---|
| `APP_URL` | **Must be the address students will open.** QR labels contain this URL, so labels printed with `http://localhost` won't work on a phone. |
| `APP_TIMEZONE` | `Europe/Riga` by default. Concert times are stored in this local time. |
| `DB_CONNECTION` | `sqlite` by default. For MySQL, also set `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD`. |
| `MAIL_MAILER` | `log` writes emails to `storage/logs/laravel.log`, which is useful locally. Use `resend` with `RESEND_API_KEY` to really send them. |
| `MAIL_FROM_ADDRESS` | The sender of invites and password-reset emails. |

## Scheduled clean-up

Deleted groups and removed students are kept for 30 days so they can be restored. After that, two commands delete them for good:

| Command | Runs |
|---|---|
| `php artisan groups:purge` | daily at 03:00 |
| `php artisan members:purge` | daily at 03:05 |

On a server, add Laravel's scheduler to cron:

```
* * * * * cd /path/to/rotadata && php artisan schedule:run >> /dev/null 2>&1
```

Locally you can run `php artisan schedule:work` instead.

## Tests

```bash
composer test        # all tests
composer test:core   # a short set of the most important tests
```

Tests use an in-memory SQLite database, so your own data is never touched. They cover:
- signing in, registration and password reset
- scanning, taking and taking over costumes
- returning costumes, and leaving a group
- adding, removing and restoring students
- concert readiness, group handover, and who may access what

## Where things are

| Path | Contents |
|---|---|
| `app/Http/Controllers/Admin/` | Teacher pages (costumes, members, concerts, group settings, reports) |
| `app/Http/Controllers/Member/` | Student pages |
| `app/Http/Controllers/ScanController.php` | Everything that happens after a QR scan |
| `app/Policies/` | Who is allowed to do what (one file per model) |
| `app/Models/` | Database models; costume hand-out logic is in `CostumeItem` |
| `app/Support/` | Season dates and dashboard activity |
| `resources/views/` | Blade templates; emails are in `emails/` |
| `resources/css/app.css` | Shared styles and the light/dark colour variables |
| `routes/web.php` | All routes (sign-in routes are in `routes/auth.php`) |
