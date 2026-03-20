# SocialBit - Task Completion Checklist

When a task is completed, ensure the following steps are performed:

## Code Quality Checks

### 1. Syntax Validation
- [ ] Run `php -l` on all modified PHP files to check syntax
- [ ] Verify no parse errors exist

### 2. Type Hints
- [ ] All parameters have type hints
- [ ] All methods have return type declarations
- [ ] Nullable types are properly declared (`?string`, `?array`, etc.)

### 3. PHPDoc Documentation
- [ ] All classes have PHPDoc blocks with description
- [ ] All public methods have PHPDoc blocks
- [ ] All parameters documented with `@param`
- [ ] All return values documented with `@return`
- [ ] Exceptions documented with `@throws` where applicable
- [ ] Special note for Controllers: Document $_FILES structure, response format, error responses
- [ ] Special note for Repositories: Document array structure for return values

### 4. Code Style (PSR-12)
- [ ] 4 spaces indentation (no tabs)
- [ ] Opening braces on same line
- [ ] Proper spacing around operators
- [ ] No trailing whitespace

### 5. Error Handling
- [ ] Error messages are descriptive (in Dutch)
- [ ] Appropriate HTTP status codes used
- [ ] Database errors handled gracefully

## Database Changes

### 6. Database Queries
- [ ] All queries use prepared statements
- [ ] Parameter types correctly specified (s, i, d)
- [ ] SQL injection prevention verified

### 7. Schema Changes
- [ ] Database schema changes documented
- [ ] Migration scripts created (if applicable)

## Version Control

### 8. Git Commit
- [ ] Changes reviewed with `git diff`
- [ ] Meaningful commit message (feat:, fix:, docs:, refactor:, etc.)
- [ ] No debug code or console.log statements
- [ ] No commented-out code blocks (remove unless needed for reference)

### 9. Code Review Prep
- [ ] Changes tested locally
- [ ] No breaking changes to existing API contracts
- [ ] Backward compatibility maintained

## Documentation

### 10. Comments and Documentation
- [ ] Complex logic explained with inline comments (Dutch)
- [ ] TODO/FIXME comments added for known issues
- [ ] README or documentation updated if needed

## Testing (When implemented)

### 11. Manual Testing
- [ ] Feature tested in browser/Postman
- [ ] Edge cases tested
- [ ] Error scenarios tested

### 12. Automated Testing (Future)
- [ ] Unit tests written (when test framework added)
- [ ] Integration tests pass (when test framework added)

## Final Check

### 13. Review
- [ ] Code follows project conventions
- [ ] No security vulnerabilities introduced
- [ ] Performance impact considered
- [ ] Dutch language used appropriately in user-facing text

## Deployment Readiness (Production)

### 14. Pre-deployment
- [ ] Environment variables checked
- [ ] Database backups taken
- [ ] Deployment plan documented
- [ ] Rollback plan prepared
