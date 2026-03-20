# Document Database Schema and Migration Process

## Overview

While DATABASE_ARCHITECTURE.md provides an excellent theoretical PostgreSQL schema, the actual codebase uses MySQL with a different structure. There's no documentation of the current database schema (posts, metrics_history, import_history, hashtag_performance tables) or how to set up/migrate the database.

## Rationale

The gap between planning docs and reality creates confusion. New developers might try to implement the PostgreSQL schema from DATABASE_ARCHITECTURE.md when the actual system uses MySQL. Clear documentation of the current state plus migration scripts would accelerate onboarding.

---
*This spec was created from ideation and is pending detailed specification.*
