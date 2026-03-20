# Add PHPDoc API Documentation for Controllers and Repositories

## Overview

Controllers (ImportController, PostController, AuthController, etc.) and Repositories lack comprehensive PHPDoc blocks. For example, ImportController.tiktok() has no documentation of the expected $_FILES structure, response format, or possible error responses. PostRepository methods are missing parameter types and return type documentation.

## Rationale

API documentation is essential for maintaining code quality and enabling team collaboration. Without proper PHPDoc, IDEs can't provide autocomplete, and developers must trace through code to understand method contracts. This affects both internal development speed and external API consumers.

---
*This spec was created from ideation and is pending detailed specification.*
