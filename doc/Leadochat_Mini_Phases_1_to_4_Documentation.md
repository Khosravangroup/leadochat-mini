# Leadochat Mini — Documentation for Phases 1 to 4

## Project Summary
Leadochat Mini is a university MVP built as a modular Laravel monolith for Meta platform integrations.  
The stack implemented so far is:

- Laravel
- Blade
- Filament
- Laravel Breeze
- Laravel Reverb
- PostgreSQL
- Docker Compose
- Nginx

The project is currently running locally on Mac mini with Docker and is prepared for later GitHub push and staging deployment.

---

## Phase 1 — Base Infrastructure

### Goal
Bring up the project skeleton with Docker and make Laravel run correctly with PostgreSQL.

### Completed Work
- Created the project folder structure
- Added `docker-compose.yml`
- Added Docker build files
- Added Nginx configuration
- Created the Laravel project in `src`
- Configured `.env`
- Connected Laravel to PostgreSQL inside Docker
- Verified Laravel app boot in browser

### Active Services
- `app`
- `nginx`
- `postgres`

### Important Fixes
- Docker daemon was not running at first
- Docker image pull/network issues were resolved
- PHP version mismatch was fixed by upgrading the app container from PHP 8.3 to PHP 8.4

### Result
The application runs successfully at:

- `http://localhost:8080`

---

## Phase 2 — Core Technical Foundation

### Goal
Install the core packages required by the MVP.

### Completed Work

#### Authentication
- Installed Laravel Breeze with Blade scaffolding
- Built frontend assets with Vite

#### Admin Panel
- Installed Filament
- Fixed Filament panel registration
- Created the initial Filament admin user

#### Realtime Foundation
- Installed Laravel Reverb
- Configured broadcasting in `.env`
- Added a dedicated `reverb` service to Docker
- Fixed Reverb runtime crash by enabling the `pcntl` PHP extension

### Active Services After Phase 2
- `app`
- `nginx`
- `postgres`
- `reverb`

### Important Fixes
- Filament default panel was missing and had to be registered correctly
- Reverb failed initially because `pcntl` was not installed in the PHP image
- Reverb environment variables were missing and had to be added manually

### Result
The following routes were successfully available:
- `/login`
- `/register`
- `/admin/login`

---

## Phase 3 — Public Website Layout and Static Pages

### Goal
Create the public-facing shell of the site and the pages required for academic presentation and future Meta review.

### Completed Work

#### Public Layout
- Built the public layout
- Built the public navbar
- Built the public footer

#### Public Pages
Created these pages:
- `/`
- `/features`
- `/about`
- `/privacy-policy`
- `/data-deletion`
- `/contact`

#### Routing
- Added a dedicated `PublicPageController`
- Registered public routes in `routes/web.php`

### Important Fixes
- Blade component path mismatch caused all public pages to fail at first
- The layout file was moved/copied to the correct component path:
  - `resources/views/components/layouts/public.blade.php`

### Result
All public pages now open correctly in the browser.

---

## Phase 4 — Workspace and Team Foundation

### Goal
Create the first version of the multi-user / multi-business structure for the project.

### Completed Work

#### Database
Created and migrated:
- `workspaces`
- `workspace_members`

#### Models and Relations
Implemented:
- `Workspace` model
- `User` ↔ `Workspace` relations
- owner/member structure

#### Registration Flow
- Registration now creates a workspace automatically
- The registered user becomes:
  - workspace owner
  - workspace member with role `owner`

#### Dashboard
Replaced the default dashboard with a real workspace overview showing:
- Logged in user
- Current workspace
- Role
- Total workspaces
- Workspace summary

### Important Fixes
- Event/listener based workspace creation caused duplicate listener registration
- That approach was removed
- Workspace creation was moved directly into `RegisteredUserController`
- This made the flow simpler and more reliable for the MVP

### Result
After registration:
- a user is created
- a workspace is created automatically
- the user is attached to `workspace_members` as `owner`

The `/dashboard` page now displays workspace information correctly.

---

## Current Project Status After Phase 4

### Completed Phases
- Phase 1 — Base Infrastructure
- Phase 2 — Core Technical Foundation
- Phase 3 — Public Website Layout
- Phase 4 — Workspace and Team Foundation

### Current Capabilities
- Local Docker-based development environment is stable
- Authentication is working
- Filament admin panel is working
- Reverb realtime server is running
- Public pages are working
- Workspace creation is automatic after registration
- Dashboard is connected to real workspace data

---

## Key Files Created or Updated

### Infrastructure
- `docker-compose.yml`
- `docker/app/Dockerfile`
- `docker/nginx/default.conf`

### Laravel Core
- `src/.env`
- `src/bootstrap/app.php`
- `src/bootstrap/providers.php`

### Public Website
- `src/app/Http/Controllers/PublicPageController.php`
- `src/routes/web.php`
- `src/resources/views/components/layouts/public.blade.php`
- `src/resources/views/components/public/navbar.blade.php`
- `src/resources/views/components/public/footer.blade.php`
- `src/resources/views/public/home.blade.php`
- `src/resources/views/public/features.blade.php`
- `src/resources/views/public/about.blade.php`
- `src/resources/views/public/privacy-policy.blade.php`
- `src/resources/views/public/data-deletion.blade.php`
- `src/resources/views/public/contact.blade.php`

### Workspace System
- `src/app/Models/User.php`
- `src/app/Models/Workspace.php`
- `src/database/migrations/*create_workspaces_table.php`
- `src/database/migrations/*create_workspace_members_table.php`
- `src/app/Http/Controllers/Auth/RegisteredUserController.php`
- `src/resources/views/dashboard.blade.php`

---

## Problems Solved So Far
- Docker daemon not running
- Docker image pull timeout problems
- PHP version incompatibility with Laravel
- Filament panel not registered
- Reverb env configuration missing
- Reverb crash because of missing `pcntl`
- Blade layout component path mismatch
- Duplicate event/listener registration for workspace creation

---

## Recommended Next Step

## Phase 5 — Connection Center
The next phase should implement:

- `provider_connections`
- `oauth_tokens`
- `provider_permissions`
- connection status UI
- `/connections` page
- preparation for:
  - Instagram Login
  - Facebook Page connection
  - WhatsApp Embedded Signup

---

## Notes
This document reflects the project state after completing the first 4 phases of implementation in the local Docker environment.
