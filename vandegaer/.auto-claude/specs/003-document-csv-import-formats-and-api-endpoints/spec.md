# Document CSV Import Formats and API Endpoints

## Overview

The GenericCsvImporter supports two CSV formats (TikTok export and content_tracker_enhanced) but there's no user-facing documentation explaining the expected column headers, data formats, or import endpoints. Users must read the PHP source code to understand what CSV formats are accepted.

## Rationale

CSV import is a primary feature for getting data into SocialBit. Marketing agencies will need clear documentation of expected column names (e.g., Content_ID, Bereik, Platform, Datum) and sample CSV files. Without this, users will experience import failures and frustration.

---
*This spec was created from ideation and is pending detailed specification.*
