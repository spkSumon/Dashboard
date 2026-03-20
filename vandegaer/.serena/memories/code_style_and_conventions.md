# SocialBit - Code Style and Conventions

## General Coding Standards

### Language
- **Primary Language**: Dutch for comments, error messages, and user-facing text
- **Code**: English class names, method names, variable names (PSR standards)
- **Comments**: Mix of Dutch and English (Dutch preferred for business logic explanations)

### Naming Conventions
- **Classes**: PascalCase (e.g., `ImportController`, `PostRepository`)
- **Methods**: camelCase (e.g., `findByPlatformPostId`, `updateLabels`)
- **Variables**: camelCase (e.g., `$userId`, `$platformPostId`)
- **Constants**: UPPER_SNAKE_CASE

### Namespaces
- Root namespace: `App\`
- Structure follows directory structure:
  - `App\Controllers`
  - `App\Repositories`
  - `App\Services`
  - `App\Middleware`
  - `App\Core`
  - `App\Helpers`

## Type Hints
- **Always use** type hints for parameters
- **Always use** return type declarations
- Use nullable types when appropriate (`?string`, `?array`, `?int`)
- Example:
  ```php
  public function find(int $id): ?array
  public function list(?string $platform, ?string $from, ?string $to, int $limit): array
  ```

## PHPDoc Standards

### Current State
- **TikTok OAuth/Analytics Services**: Good PHPDoc coverage with @param, @return, @throws
- **Controllers**: Minimal or no PHPDoc blocks (NEEDS IMPROVEMENT)
- **Repositories**: Some inline comments, incomplete parameter and return documentation (NEEDS IMPROVEMENT)

### Required PHPDoc Format
Every class, method, and property should have a PHPDoc block:

#### Class Documentation
```php
/**
 * Short description of the class purpose.
 *
 * Longer description if needed, explaining the responsibility of this class.
 *
 * @package App\Controllers
 */
class ExampleController {
```

#### Method Documentation
```php
/**
 * Short description of what the method does.
 *
 * Longer description if needed, explaining:
 * - Expected input format (especially for $_FILES, request body structure)
 * - Response format (JSON structure, status codes)
 * - Side effects (database writes, external API calls)
 * - Error conditions and responses
 *
 * @param string $param1 Description of parameter
 * @param int|null $param2 Description of optional parameter
 * @return array Description of return value structure
 * @throws \Exception Description of when exception is thrown
 */
public function exampleMethod(string $param1, ?int $param2): array {
```

#### Property Documentation
```php
/**
 * Description of what this property holds.
 *
 * @var TypeName
 */
private $propertyName;
```

### API Endpoint Documentation (Controllers)
For controller methods that handle HTTP requests:

```php
/**
 * Import TikTok analytics from CSV file.
 *
 * Expected $_FILES structure:
 * - 'file': Uploaded CSV file with columns: [Video link, Date posted, Views, etc.]
 *
 * Response format (success):
 * {
 *   "imported": 15,
 *   "skipped": 2,
 *   "errors": []
 * }
 *
 * Response format (error):
 * {
 *   "error": "Error message",
 *   "code": 500
 * }
 *
 * @return void Sends JSON response and exits
 */
public function tiktok(): void {
```

### Repository Method Documentation
For database operations:

```php
/**
 * Find post by platform and platform_post_id.
 *
 * @param string $platform Platform name (e.g., 'tiktok', 'instagram')
 * @param string $platformPostId Platform-specific post identifier
 * @return array|null Post data array or null if not found
 */
public function findByPlatformPostId(string $platform, string $platformPostId): ?array {
```

## Error Handling
- Use `Response::error()` for HTTP error responses
- Use `Response::json()` for successful JSON responses
- Include descriptive Dutch error messages
- Example:
  ```php
  if (!$username || !$password) {
      Response::error('Gebruikersnaam of wachtwoord ontbreekt', 400);
  }
  ```

## Database Queries
- Use prepared statements with parameter binding
- Type string format: 's' (string), 'i' (integer), 'd' (double)
- Example:
  ```php
  $this->db->exec(
      "UPDATE posts SET post_type = ?, topic = ? WHERE id = ?",
      "ssi",
      [$postType, $topic, $id]
  );
  ```

## Dependency Injection
- Use constructor injection for dependencies
- Example:
  ```php
  public function __construct(PostRepository $posts) {
      $this->posts = $posts;
  }
  ```

## PSR Standards
- Follow PSR-1 (Basic Coding Standard)
- Follow PSR-12 (Extended Coding Style)
- Use 4 spaces for indentation (not tabs)
- Opening braces on same line for methods/classes
