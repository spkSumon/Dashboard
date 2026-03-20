# SocialBit - Tech Stack

## Backend
- **PHP**: 8.2+ (vanilla PHP, no framework)
- **Architecture**: Custom MVC pattern
  - Controllers: HTTP layer (src/Controllers/)
  - Repositories: Database queries (src/Repositories/)
  - Services: Business logic (src/Services/)
  - Middleware: Auth, etc. (src/Middleware/)
  - Core: Database, Router (src/Core/)
  - Helpers: Request, Response, Validation (src/Helpers/)

## Database
- **MySQL** (development)
- **PostgreSQL** (planned for production)
- Schema v2 with `posted_date` column
- Tables: posts, metrics_history, settings, tiktok_tokens, users

## Frontend (Planned)
- Alpine.js 3.x
- TailwindCSS 4
- Chart.js 4
- Heroicons

## External APIs
- Instagram Graph API
- TikTok OAuth & Analytics API
- Fathom Analytics API
- RiteKit API (hashtag suggestions)

## Hosting
- Plesk Obsidian server
- XAMPP for local development (Windows)

## File Structure
```
socialbit-live/
├── config/          # Configuration files
├── public/          # Public web root
├── scripts/         # Utility scripts
├── src/            # Application code
│   ├── Controllers/
│   ├── Core/
│   ├── Helpers/
│   ├── Middleware/
│   ├── Repositories/
│   └── Services/
└── storage/        # Storage directory
```
