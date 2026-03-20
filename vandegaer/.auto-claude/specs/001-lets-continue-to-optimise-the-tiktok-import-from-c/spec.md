# Specification: TikTok CSV Import Optimization

## Overview

This task focuses on optimizing the TikTok CSV import functionality in the Social Media Analytics POC application. The current implementation in `TikTokCsvImporter.php` and `GenericCsvImporter.php` processes CSV files row-by-row with multiple database queries per record, which becomes inefficient for large datasets. The optimization will introduce batch processing, memory-efficient streaming for large files, and reduced database round-trips to significantly improve import performance while maintaining data integrity.

## Workflow Type

**Type**: feature

**Rationale**: This is an enhancement to existing functionality. The import feature already works but requires performance optimization to handle larger datasets efficiently. This involves modifying the internal processing logic without changing the API contract or user interface.

## Task Scope

### Services Involved
- **src/Services** (primary) - Contains the CSV import services that need optimization
- **src/Controllers** (integration) - Import controller that exposes the import endpoints
- **src/Core** (reference) - Database wrapper with helper methods

### This Task Will:
- [ ] Implement batch insert processing for posts (groups of 50-100 rows)
- [ ] Implement batch insert processing for metrics_history records
- [ ] Optimize hashtag processing to reduce individual queries
- [ ] Add memory-efficient file streaming for large CSV files
- [ ] Reduce prepared statement creation overhead
- [ ] Add progress tracking capability for large imports
- [ ] Maintain backward compatibility with existing API endpoints

### Out of Scope:
- Changes to the CSV file format requirements
- Frontend/UI modifications
- Changes to database schema
- API endpoint changes (contracts remain the same)
- Changes to authentication or authorization

## Service Context

### Primary Service: CSV Importers

**Tech Stack:**
- Language: PHP 8.x
- Framework: Custom vanilla PHP (no ORM)
- Database: MySQL via mysqli (prepared statements)
- Key directories: `src/Services/`, `src/Controllers/`

**Entry Points:**
- `src/Services/TikTokCsvImporter.php` - Legacy TikTok-specific importer
- `src/Services/GenericCsvImporter.php` - Multi-format importer (primary target)

**How to Run:**
```bash
# Start Apache + MySQL via XAMPP
# Access via: http://localhost/socialbit-live/
```

**Port:** 80 (XAMPP Apache)

### Supporting: Database Core

**Entry Point:** `src/Core/Database.php`

**Key Methods:**
- `conn()` - Returns mysqli connection
- `prepare()` - Creates prepared statements with auto-type detection
- `fetchAll()`, `fetchOne()`, `exec()` - Query execution helpers

## Files to Modify

| File | Service | What to Change |
|------|---------|---------------|
| `src/Services/GenericCsvImporter.php` | Services | Implement batch processing, streaming, optimized hashtag handling |
| `src/Services/TikTokCsvImporter.php` | Services | Add batch insert capability, share optimization patterns |
| `src/Controllers/ImportController.php` | Controllers | Add optional progress callback support |

## Files to Reference

These files show patterns to follow:

| File | Pattern to Copy |
|------|----------------|
| `src/Core/Database.php` | Prepared statement pattern, transaction handling |
| `src/Services/GenericCsvImporter.php` | Current import flow, field mapping, format detection |
| `scripts/000_create_database_schema.sql` | Table structure for batch insert SQL construction |

## Patterns to Follow

### Current Import Pattern (to optimize)

From `src/Services/GenericCsvImporter.php`:

```php
// Current: Row-by-row processing with 3+ queries per row
while (($row = fgetcsv($fh)) !== false) {
    // 1. posts upsert
    $postsUpsert->bind_param(...);
    $postsUpsert->execute();

    // 2. get post_id
    $getPostId->bind_param(...);
    $getPostId->execute();

    // 3. metrics_history insert
    $mhInsert->bind_param(...);
    $mhInsert->execute();

    // 4. Multiple hashtag queries per post
    $this->processHashtags(...);
}
```

**Key Points:**
- Single transaction wrapping is good (keep this)
- Row-by-row = N database round trips for N rows (optimize this)
- Prepared statements created once (good pattern)
- Hashtag processing creates 2-3 queries per hashtag per post (optimize this)

### Target Pattern: Batch Processing

```php
// Target: Collect rows into batches, insert in groups
$batch = [];
$batchSize = 100;

while (($row = fgetcsv($fh)) !== false) {
    $mapped = $this->mapRow($row, $format);
    if ($mapped) {
        $batch[] = $mapped;
    }

    if (count($batch) >= $batchSize) {
        $this->insertBatch($conn, $batch);
        $batch = [];
    }
}

// Insert remaining
if (!empty($batch)) {
    $this->insertBatch($conn, $batch);
}
```

**Key Points:**
- Collect mapped rows into batches
- Use multi-row INSERT for posts
- Use INSERT ... ON DUPLICATE KEY UPDATE with multiple rows
- Process hashtags in bulk after batch inserts

### Database Transaction Pattern

From `src/Core/Database.php`:

```php
$conn = $this->db->conn();
$conn->begin_transaction();

try {
    // ... operations
    $conn->commit();
} catch (\Throwable $e) {
    $conn->rollback();
    throw $e;
}
```

**Key Points:**
- Always use transactions for batch operations
- Rollback on any error
- Consider chunked commits for very large files (10k+ rows)

## Requirements

### Functional Requirements

1. **Batch Insert Processing**
   - Description: Group multiple CSV rows into batches and insert using multi-row INSERT statements
   - Acceptance: Import of 1000 rows should use ~10-20 INSERT statements instead of 3000+

2. **Memory-Efficient Streaming**
   - Description: Process large CSV files without loading entire file into memory
   - Acceptance: Files up to 50MB should import without memory errors (current 128MB limit)

3. **Optimized Hashtag Processing**
   - Description: Collect hashtags from batch and process together instead of per-row
   - Acceptance: Hashtag queries reduced from ~3 per hashtag to batch operations

4. **Progress Tracking (Optional)**
   - Description: Add ability to track import progress for long-running imports
   - Acceptance: Progress callback receives total rows, processed count, skipped count

5. **Backward Compatibility**
   - Description: Existing API endpoints and response format must remain unchanged
   - Acceptance: Existing frontend import form works without modifications

### Edge Cases

1. **Empty CSV file** - Return `processed: 0, skipped: 0` without error
2. **CSV with only headers** - Return `processed: 0, skipped: 0` without error
3. **Very large files (50MB+)** - Use chunked transaction commits every 5000 rows
4. **Malformed rows** - Skip and count in `skipped`, continue processing
5. **Duplicate post_ids in same batch** - Handle gracefully with ON DUPLICATE KEY UPDATE
6. **Database connection timeout** - Ensure transaction is rolled back, return meaningful error
7. **Memory limit approaching** - Flush batch early if memory usage exceeds threshold

## Implementation Notes

### DO
- Follow the existing prepared statement pattern from `Database.php`
- Reuse the `normalizeHeader()` and field mapping methods unchanged
- Keep transaction boundaries - one transaction per import (or chunked for huge files)
- Use multi-row INSERT syntax: `INSERT INTO table VALUES (...), (...), (...)`
- Test with various CSV sizes: 10 rows, 100 rows, 1000 rows, 10000 rows
- Maintain the same return format: `['processed' => n, 'skipped' => m, 'format' => '...']`
- Add comments in Dutch/English as per existing code style

### DON'T
- Don't create new database tables or modify schema
- Don't change the API endpoint paths or response format
- Don't use external libraries (keep vanilla PHP approach)
- Don't remove the legacy `TikTokCsvImporter.php` - optimize it too
- Don't change the format detection logic
- Don't load entire file into memory at once

## Development Environment

### Start Services

```bash
# Start XAMPP (Apache + MySQL)
# On Windows: C:\xampp3\xampp-control.exe
# Or command line:
C:\xampp3\apache\bin\httpd.exe
C:\xampp3\mysql\bin\mysqld.exe
```

### Service URLs
- Application: http://localhost/socialbit-live/
- API Base: http://localhost/socialbit-live/api/

### Relevant API Endpoints
- POST `/api/import/tiktok` - Legacy TikTok importer
- POST `/api/import/csv` - Generic importer (auto-detect format)

### Test CSV Files
- `scripts/content_tracker_enhanced.csv` - Sample content tracker format

### Required Environment Variables
- Database config in `src/Core/Database.php` constructor or config file
- `host`: 127.0.0.1
- `port`: 3306
- `name`: social_media_analytics
- `user`: root (XAMPP default)
- `pass`: (empty or configured)

## Success Criteria

The task is complete when:

1. [ ] Batch insert processing implemented for posts (50-100 rows per batch)
2. [ ] Batch insert processing implemented for metrics_history
3. [ ] Hashtag processing optimized to reduce queries by 80%+
4. [ ] Import of 1000 rows completes in <5 seconds (vs current ~15-30 seconds)
5. [ ] Memory usage stays under 64MB for files up to 10000 rows
6. [ ] No console errors during import
7. [ ] Existing tests still pass (if any exist)
8. [ ] Frontend import form works unchanged
9. [ ] Import history correctly logged
10. [ ] All existing CSV formats still supported

## QA Acceptance Criteria

**CRITICAL**: These criteria must be verified by the QA Agent before sign-off.

### Unit Tests
| Test | File | What to Verify |
|------|------|----------------|
| Batch Insert Posts | `tests/Services/GenericCsvImporterTest.php` | Batch of 100 rows inserts correctly |
| Batch Insert Metrics | `tests/Services/GenericCsvImporterTest.php` | Metrics history records created for all posts |
| Empty CSV Handling | `tests/Services/GenericCsvImporterTest.php` | Returns 0 processed, no error |
| Malformed Row Skip | `tests/Services/GenericCsvImporterTest.php` | Skipped count incremented, processing continues |
| Format Detection | `tests/Services/GenericCsvImporterTest.php` | content_tracker and tiktok_export formats detected |

### Integration Tests
| Test | Services | What to Verify |
|------|----------|----------------|
| Full Import Flow | GenericCsvImporter ↔ Database | Data correctly inserted into posts, metrics_history tables |
| Hashtag Linking | GenericCsvImporter ↔ Database | hashtag_performance and post_hashtags populated |
| Transaction Rollback | GenericCsvImporter ↔ Database | On error, no partial data committed |
| Import History | GenericCsvImporter ↔ Database | import_history record created with correct counts |

### End-to-End Tests
| Flow | Steps | Expected Outcome |
|------|-------|------------------|
| TikTok CSV Import | 1. Upload TikTok export CSV 2. Submit form | Posts appear in table, success message shown |
| Content Tracker Import | 1. Upload content_tracker CSV 2. Submit form | Posts with correct platform appear, hashtags linked |
| Large File Import | 1. Upload 5000 row CSV 2. Submit form | Completes within timeout, all rows processed |

### Browser Verification (if frontend)
| Page/Component | URL | Checks |
|----------------|-----|--------|
| Import Form | `http://localhost/socialbit-live/#/posts` | Form visible, file input works |
| Import Status | `http://localhost/socialbit-live/#/posts` | Success message shows processed/skipped counts |
| Posts Table | `http://localhost/socialbit-live/#/posts` | Imported posts appear after refresh |

### Database Verification (if applicable)
| Check | Query/Command | Expected |
|-------|---------------|----------|
| Posts inserted | `SELECT COUNT(*) FROM posts WHERE import_source='csv'` | Count matches processed rows |
| Metrics history | `SELECT COUNT(*) FROM metrics_history mh JOIN posts p ON mh.post_id = p.id WHERE p.import_source='csv'` | One metrics record per post |
| Hashtags linked | `SELECT COUNT(*) FROM post_hashtags` | Hashtags linked to posts with hashtag data |
| Import logged | `SELECT * FROM import_history ORDER BY id DESC LIMIT 1` | Latest import shows correct filename, counts |

### Performance Verification
| Metric | Measurement | Target |
|--------|-------------|--------|
| 100 row import | Time from submit to complete | < 1 second |
| 1000 row import | Time from submit to complete | < 5 seconds |
| 5000 row import | Time from submit to complete | < 20 seconds |
| Memory usage | Peak memory during 5000 row import | < 64MB |

### QA Sign-off Requirements
- [ ] All unit tests pass
- [ ] All integration tests pass
- [ ] All E2E tests pass
- [ ] Browser verification complete
- [ ] Database state verified
- [ ] Performance targets met
- [ ] No regressions in existing functionality
- [ ] Code follows established patterns (transactions, prepared statements)
- [ ] No security vulnerabilities introduced (SQL injection, file handling)
- [ ] Error handling maintains graceful degradation
