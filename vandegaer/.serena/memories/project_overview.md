# SocialBit - Project Overview (Updated 2025-12-31)

## Purpose
SocialBit is a data-focused social media analytics platform for tracking performance across TikTok, Instagram, and Facebook. It emphasizes reliable data collection, storage, and insights over UI polish.

## Current Status
- **Version**: v1.0 (POC)
- **Live URL**: https://socialbit.g-bit.be/
- **Developer**: Single developer (Bjorn)
- **Timeline**: 6 months to functional MVP

## What Works RIGHT NOW ✅
- TikTok CSV import (fully functional)
- Posts table with comprehensive metrics
- Time-series metrics tracking (metrics_history)
- Hashtag performance analytics
- Content planning system
- Basic authentication (admin/admin123)
- Import history tracking
- Analytics views for quick queries

## Architecture
- **Backend**: Custom PHP 8.2+ (vanilla, no framework)
- **Database**: MySQL/MariaDB on Plesk
- **Pattern**: 3-layer MVC (Controller → Service → Repository)
- **All Controllers and Repositories**: Fully documented with comprehensive PHPDoc

## Next Phase (Month 1-2)
**Multi-Tenant Foundation**
- Add `clients` table for customer management
- Update `posts` with `client_id` for data isolation
- Create `social_accounts` table for OAuth tokens
- Implement client management UI
- Test with 3-5 dummy clients

## Key Documents
- **PROJECT_CONTEXT.md**: Single source of truth for project state
- **FEATURE_ROADMAP.md**: 6-month development plan
- **DATABASE_EVOLUTION.md**: Multi-tenant migration strategy
- **API_DATA_COLLECTION.md**: Instagram/TikTok API integration

## Philosophy
**Data First, UI Second** - Focus on reliable data collection and insights, not fancy interfaces.
