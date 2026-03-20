-- Migration 011: 2026 Algorithm Metrics
-- Adds watch time, completion rate, and quality engagement metrics
-- Created: 2026-02-07
-- Purpose: Track algorithm-prioritized metrics for TikTok, Instagram, YouTube

-- Add watch time and completion metrics
ALTER TABLE posts ADD COLUMN watch_time INT DEFAULT 0 AFTER views;
ALTER TABLE posts ADD COLUMN average_watch_time INT DEFAULT 0 AFTER watch_time;
ALTER TABLE posts ADD COLUMN completion_rate DECIMAL(5,2) DEFAULT 0.00 AFTER average_watch_time;
ALTER TABLE posts ADD COLUMN duration INT DEFAULT 0 AFTER completion_rate;

-- Add engagement quality metrics
ALTER TABLE posts ADD COLUMN sends_count INT DEFAULT 0 AFTER shares;
ALTER TABLE posts ADD COLUMN profile_visits INT DEFAULT 0 AFTER sends_count;
ALTER TABLE posts ADD COLUMN skip_rate DECIMAL(5,2) DEFAULT 0.00 AFTER profile_visits;

-- Add follower growth tracking
ALTER TABLE posts ADD COLUMN follower_growth INT DEFAULT 0 AFTER skip_rate;

-- Add crossposted views (Instagram 2026 feature)
ALTER TABLE posts ADD COLUMN crossposted_views INT DEFAULT 0 AFTER views;
