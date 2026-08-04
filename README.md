# Library Booking System

## Directory layout

- `index.php`, `login.php`, `admin.php`, `api.php`, `logout.php` - public PHP entry points.
- `components/` - reusable PHP view fragments and modals.
- `css/`, `js/`, `img/` - browser assets.
- `packages/core/` - application services, database access, mail worker, and runtime data.
- `packages/phpmailer/` - bundled PHPMailer dependency.
- `packages/cli/` - command-line scripts.
- `tools/diagnostics/` - database inspection scripts for local troubleshooting.
- `tools/scratch/` - archived scratch files and one-off working notes.

## Diagnostics

Run diagnostics from the project root:

```sh
php tools/diagnostics/inspect_sessions.php
php tools/diagnostics/dump_schema.php
```
