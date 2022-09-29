# Changelog

Introduced for version 4.0.

## 4.0.0 - 2022-09-29

### Breaking Changes
- Changed signatures of all methods with stricter types.
- Removed many fluent syntax implementations (return `$this`).
- `pluck()` now returns a Collection instance, not an array.
- Removed `lists()` method.
- Removed Artisan command to make repository from stub.
  I felt this was a minor feature, not worth the hassle of upgrading.
- Removed ExtendedPostProcessing variant of the repository.
  If you depend on this, either rebuild it for your local needs, or stick with an older version.

## 3.0.0

Laravel 9 support without breaking changes.
