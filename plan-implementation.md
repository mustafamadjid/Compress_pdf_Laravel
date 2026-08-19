# AI Agent Implementation Plan
# PDF Compressor — Laravel MVC + Livewire

## 1. Purpose

This document defines a phased implementation plan for an AI coding agent.

The main objective is to prevent the agent from implementing the entire application in a single large change.

Each phase should:

- Have one clear responsibility.
- Produce a runnable or verifiable increment.
- Include tests or explicit verification.
- Avoid implementing future-phase requirements prematurely.
- Be reviewed before moving to the next phase.

The source of product requirements is:

```text
prd.md
```

---

## 2. Implementation Principles

The AI agent must follow these principles throughout development.

### 2.1 Keep the Architecture Simple

Use:

```text
Laravel MVC
Blade
Livewire
Service class for PDF compression
Laravel filesystem
Laravel scheduler
```

Do not introduce:

- Repository pattern without a persistence requirement.
- Domain-driven design layers.
- Separate frontend application.
- REST API.
- Database tables.
- Authentication.
- Redis.
- Queues.

unless a later requirement explicitly requires them.

---

### 2.2 Do Not Implement Multiple Phases at Once

For every phase:

1. Read `prd.md`.
2. Read the current phase in this document.
3. Inspect the existing implementation.
4. Implement only the scope of the current phase.
5. Run relevant tests.
6. Report:
   - Changed files.
   - Tests executed.
   - Test results.
   - Remaining known issues.
7. Stop after the phase is complete.

Do not automatically continue into the next phase.

---

### 2.3 Preserve Existing Working Behavior

Before modifying existing code:

- Inspect the relevant classes and views.
- Avoid unnecessary refactoring.
- Keep naming consistent with the existing codebase.
- Prefer small commits/changes over broad rewrites.

---

### 2.4 Security Baseline

Never:

- Build shell commands from raw request values.
- Trust client file extensions alone.
- Expose server filesystem paths.
- Expose command output in UI errors.
- Store uploaded PDFs under `public/`.
- Use the original uploaded filename as the internal storage filename.

---

## 3. Recommended Final Structure

Target structure:

```text
app/
├── Enums/
│   └── CompressionLevel.php
│
├── Exceptions/
│   └── PdfCompressionException.php
│
├── Livewire/
│   └── PdfCompressor.php
│
├── Services/
│   └── PdfCompressionService.php
│
└── Console/
    └── Commands/
        └── CleanupTemporaryPdfFiles.php

config/
└── pdf-compressor.php

resources/
└── views/
    ├── livewire/
    │   └── pdf-compressor.blade.php
    └── welcome.blade.php

storage/
└── app/
    └── temporary/
        ├── uploads/
        └── compressed/

tests/
├── Feature/
│   └── PdfCompressionTest.php
└── Unit/
    └── PdfCompressionServiceTest.php
```

This is a target, not a requirement to generate all files in Phase 1.

---

# Phase 0 — Project Bootstrap

## Goal

Prepare a clean Laravel application with Livewire installed and confirm the project can run.

## Scope

Implement only project/bootstrap configuration.

### Tasks

- Create or verify Laravel project.
- Install Livewire.
- Verify Blade rendering.
- Verify Tailwind setup available in the Laravel frontend stack.
- Configure application name.
- Add a basic home route.
- Render a minimal placeholder page.
- Add `.env.example` entries that will later be used by the PDF compressor where appropriate.
- Confirm test runner works.

Do not implement PDF upload or Ghostscript integration yet.

---

## Expected Output

The browser should display a simple page such as:

```text
PDF Compressor
Application setup completed.
```

---

## Verification

Run:

```bash
php artisan test
```

and the relevant frontend build command, for example:

```bash
npm run build
```

---

## Completion Criteria

- [ ] Laravel application starts successfully.
- [ ] Livewire is installed and working.
- [ ] Home page renders.
- [ ] Frontend assets build successfully.
- [ ] Test suite can execute.
- [ ] No PDF-specific business logic exists yet.

---

# Phase 1 — Configuration and Compression Domain Types

## Goal

Create the internal configuration and strongly controlled compression-level model.

## Scope

No file upload UI and no real Ghostscript execution yet.

### Tasks

Create:

```text
config/pdf-compressor.php
```

Configuration should support:

```text
max_upload_mb
retention_minutes
process_timeout
ghostscript_binary
```

Add environment variables to:

```text
.env.example
```

Create:

```text
app/Enums/CompressionLevel.php
```

Expected values:

```text
low
medium
high
```

Add behavior required to safely map application-level compression options to internal service parameters.

If Ghostscript presets are represented in the enum or service, raw user input must never become a shell argument directly.

---

## Testing

Create focused unit tests for:

- Valid compression levels.
- Default compression level where applicable.
- Mapping behavior.
- Configuration loading.

---

## Do Not Implement Yet

- Livewire upload.
- Actual file compression.
- Download route.
- Temporary cleanup command.

---

## Completion Criteria

- [ ] Compression levels are represented by application code.
- [ ] Configuration values are centralized.
- [ ] No raw request value can define Ghostscript arguments.
- [ ] Unit tests pass.

---

# Phase 2 — PDF Compression Service

## Goal

Implement the backend service responsible for compressing a PDF.

## Scope

Implement the service independently from the UI.

### Tasks

Create:

```text
app/Services/PdfCompressionService.php
```

Create:

```text
app/Exceptions/PdfCompressionException.php
```

The service should accept conceptually:

```php
compress(
    string $sourcePath,
    string $destinationPath,
    CompressionLevel $level
)
```

The exact method signature may be adjusted to fit Laravel conventions.

Responsibilities:

1. Verify the source file exists.
2. Ensure the destination directory exists.
3. Resolve the compression preset.
4. Invoke Ghostscript safely.
5. Apply a process timeout.
6. Detect a non-zero exit code.
7. Confirm the output file exists.
8. Confirm the output is non-empty.
9. Return useful metadata or a result object/array.
10. Convert infrastructure failures into `PdfCompressionException`.

Use Laravel/Symfony process facilities rather than raw unescaped shell concatenation.

---

## Important Security Constraint

Do not implement command execution like:

```php
exec("gs ... " . $userInput);
```

Use structured process arguments.

The only compression-level values allowed are those provided by `CompressionLevel`.

---

## Testing Strategy

The test suite should not depend exclusively on Ghostscript being installed on every CI environment.

Prefer designing process execution so failure/success behavior can be tested independently where practical.

At minimum test:

- Missing source file.
- Process failure.
- Missing output.
- Successful metadata calculation.
- Compression-level parameter mapping.

If an integration test requiring Ghostscript is added, clearly mark or separate it.

---

## Manual Verification

When Ghostscript is installed locally:

```text
sample.pdf
    ↓
PdfCompressionService
    ↓
compressed-sample.pdf
```

Verify the resulting document opens correctly.

---

## Do Not Implement Yet

- Livewire component.
- User upload.
- Download button.
- Scheduled cleanup.

---

## Completion Criteria

- [ ] Compression service exists.
- [ ] Process execution is protected from command injection.
- [ ] Timeout is configured.
- [ ] Failures use application-specific exceptions.
- [ ] Generated output is validated.
- [ ] Unit tests pass.

---

# Phase 3 — Livewire Upload and Validation

## Goal

Build the user-facing PDF upload workflow without completing the download/result experience yet.

## Scope

Create the main Livewire component.

### Tasks

Create:

```text
app/Livewire/PdfCompressor.php
```

and:

```text
resources/views/livewire/pdf-compressor.blade.php
```

The component must support:

- One PDF upload.
- Compression-level selection.
- `medium` as default.
- File-size validation.
- PDF MIME validation.
- Display selected file name.
- Display selected file size.
- Loading/disabled state during actions.

Store uploads in a dedicated temporary directory.

Example:

```text
storage/app/temporary/uploads
```

Use generated internal names.

Do not trust the original filename for storage paths.

---

## UI Scope

Implement only the basic application interface:

```text
Title
Description
Upload input/drop area
Selected file information
Compression level selector
Compress button
Validation messages
```

Avoid visual overengineering.

---

## Tests

Feature/Livewire tests must cover:

- Valid PDF.
- Invalid extension.
- Invalid MIME type.
- Oversized PDF.
- Default compression level.
- Changing compression level.
- Component state reset where relevant.

Use fake storage where practical.

---

## Do Not Implement Yet

- Full compression-result card.
- Download endpoint.
- Temporary cleanup command.

The component may call a temporary stub or may delay wiring the actual compression action until Phase 4, depending on the cleanest incremental implementation.

---

## Completion Criteria

- [ ] User can select one PDF.
- [ ] Invalid files are rejected.
- [ ] Upload is stored outside `public/`.
- [ ] Internal filenames are generated.
- [ ] Compression level can be selected.
- [ ] Medium is default.
- [ ] Livewire tests pass.

---

# Phase 4 — End-to-End Compression Workflow

## Goal

Connect the Livewire upload workflow to `PdfCompressionService`.

## Scope

Implement the complete compression action, but not the final download mechanism yet if keeping that isolated reduces risk.

### Tasks

Wire:

```text
PdfCompressor
    ↓
PdfCompressionService
```

On compression:

1. Validate state again.
2. Generate the output identifier/path.
3. Execute compression.
4. Handle `PdfCompressionException`.
5. Store result metadata in Livewire state.

The result state should include:

```text
original filename
original size
compressed size
reduction percentage
compression level
generated file identifier
```

Do not expose full filesystem paths to the browser.

---

## Reduction Formula

Conceptually:

```text
reduction_percentage =
((original_size - compressed_size) / original_size) * 100
```

Handle a zero-byte source defensively even though validation should reject invalid files.

---

## UI Result Card

Add:

```text
Compression Complete

Original Size
Compressed Size
Reduction Percentage
Compression Level
```

Handle the case where the compressed file is equal to or larger than the source.

Suggested message:

```text
This PDF is already well optimized and could not be reduced significantly.
```

---

## Error Handling

User-facing errors must remain generic.

Example:

```text
The PDF could not be compressed. Please try another file.
```

Technical details should go to Laravel logs.

---

## Tests

Test:

- Successful compression action.
- Service exception.
- Correct metadata.
- Result state.
- Output-larger-than-original behavior.
- Button/process state if testable.

Mock or fake the compression service where appropriate so feature tests do not become Ghostscript integration tests.

---

## Completion Criteria

- [ ] Valid upload can invoke the compression service.
- [ ] Result metadata is displayed.
- [ ] Internal paths remain hidden.
- [ ] Compression failures are handled safely.
- [ ] Technical failure is logged.
- [ ] Tests pass.

---

# Phase 5 — Secure Download Flow

## Goal

Allow the generated compressed PDF to be downloaded securely.

## Scope

Implement only generated-file download behavior.

### Tasks

Create a download mechanism using an opaque generated identifier.

The client must not send:

```text
/storage/app/temporary/compressed/example.pdf
```

as an arbitrary path.

Use either:

- A signed route.
- A generated token/identifier resolved server-side.
- Another simple Laravel-native secure approach.

Output filename:

```text
compressed-{sanitized-original-name}.pdf
```

Verify the file exists before returning it.

If the file has expired or disappeared:

```text
This compressed file is no longer available. Please compress the PDF again.
```

---

## Security Tests

Test:

- Valid generated file download.
- Missing file.
- Expired/nonexistent identifier.
- Arbitrary path traversal attempts.
- Identifier belonging to no generated file.
- Content-Disposition filename.

---

## Completion Criteria

- [ ] User can download compressed result.
- [ ] No filesystem path is exposed.
- [ ] Arbitrary files cannot be downloaded.
- [ ] Missing files fail gracefully.
- [ ] Download tests pass.

---

# Phase 6 — Reset and Temporary File Cleanup

## Goal

Complete the temporary-file lifecycle.

## Scope

Implement state reset and scheduled cleanup.

### Tasks

Add a Livewire action:

```text
resetCompression
```

It should reset:

- Uploaded file.
- Compression level if desired back to medium.
- Result metadata.
- Download identifier.
- Error state.

Create:

```text
app/Console/Commands/CleanupTemporaryPdfFiles.php
```

The command must:

1. Read retention period from configuration.
2. Scan only the application-controlled temporary PDF directories.
3. Delete files older than the retention threshold.
4. Avoid deleting unrelated application files.
5. Log cleanup failures where appropriate.

Register the command with Laravel scheduler.

Suggested cadence:

```text
Every 15–30 minutes
```

Retention and schedule can remain simple for MVP.

---

## Tests

Test:

- Old temporary files are deleted.
- Fresh files remain.
- Cleanup only touches expected directories.
- Reset clears component state.
- Missing directories are handled safely.

---

## Completion Criteria

- [ ] User can compress another PDF without refreshing the page.
- [ ] Old uploads are cleaned automatically.
- [ ] Old compressed files are cleaned automatically.
- [ ] Fresh files are preserved.
- [ ] Cleanup tests pass.

---

# Phase 7 — UI Polish and Responsive Behavior

## Goal

Improve usability without adding product scope.

## Scope

Only presentation and interaction improvements.

### Tasks

Improve:

- Upload area.
- Compression-level controls.
- Button hierarchy.
- Loading indicator.
- Result card.
- Validation messages.
- Mobile layout.
- Spacing and typography.

Ensure:

- Compress button cannot be double-submitted.
- Loading state is visible.
- File-size values are human readable.
- Buttons have clear labels.
- Keyboard navigation remains usable.

---

## Constraints

Do not add:

- Authentication.
- Dashboard.
- History.
- Batch upload.
- New PDF tools.
- Animations that materially complicate the UI.

---

## Manual Verification

Check at minimum:

```text
Desktop width
Tablet width
Mobile width
```

Verify the whole workflow visually.

---

## Completion Criteria

- [ ] UI is responsive.
- [ ] Loading state is clear.
- [ ] Error state is clear.
- [ ] Result state is clear.
- [ ] Main workflow remains one-page and simple.

---

# Phase 8 — Hardening and Edge Cases

## Goal

Validate the application against realistic invalid and unusual inputs.

## Scope

Security and reliability improvements only.

### Test Cases

Validate behavior for:

- Empty upload.
- Fake `.pdf` extension.
- Corrupted PDF.
- Password-protected PDF.
- Very large PDF near configured limit.
- Already optimized PDF.
- Text-only PDF.
- Image-heavy PDF.
- Scanned PDF.
- Ghostscript missing.
- Process timeout.
- Permission failure.
- Temporary directory unavailable.
- Output missing after process completes.
- Output is zero bytes.
- Output is larger than source.
- User waits until generated file has expired.

---

## Tasks

- Add missing guards discovered during testing.
- Improve error classification where it improves maintainability.
- Confirm logs contain technical details but UI does not.
- Verify no temporary files are publicly accessible.
- Confirm no shell argument derives directly from request values.
- Confirm upload size is enforced both by Laravel and documented server/runtime configuration where required.

---

## Completion Criteria

- [ ] Critical edge cases have automated or documented manual tests.
- [ ] Compression failures never expose internal paths.
- [ ] Application handles missing Ghostscript safely.
- [ ] Process timeout works.
- [ ] Corrupted files fail gracefully.
- [ ] Security requirements in `prd.md` are satisfied.

---

# Phase 9 — Final Test Suite and Release Readiness

## Goal

Verify that the MVP matches the PRD before deployment.

## Scope

No new product features.

### Tasks

Run the complete test suite.

Example:

```bash
php artisan test
npm run build
```

Run code formatting/linting configured by the project.

Example:

```bash
./vendor/bin/pint
```

Verify production requirements:

- PHP extensions.
- Ghostscript installation.
- Writable storage.
- Scheduler configuration.
- Upload-size limits.
- Process timeout.
- Web-server body-size limits.
- PHP `upload_max_filesize`.
- PHP `post_max_size`.

Create or update:

```text
README.md
```

with:

- Local setup.
- Ghostscript requirement.
- Environment variables.
- Running the application.
- Running tests.
- Running cleanup command.
- Scheduler requirement.
- Production considerations.

---

## PRD Acceptance Review

Check every item in:

```text
prd.md → Acceptance Criteria
```

Do not mark the implementation complete while a mandatory acceptance criterion remains unsatisfied.

---

## Completion Criteria

- [ ] Full test suite passes.
- [ ] Frontend production build passes.
- [ ] Code formatting passes.
- [ ] README contains setup instructions.
- [ ] Ghostscript dependency is documented.
- [ ] Scheduler dependency is documented.
- [ ] All MVP acceptance criteria are checked.
- [ ] No post-MVP features were accidentally introduced.

---

# 4. Optional Phase 10 — Dockerization

## Goal

Provide a reproducible deployment image if Docker is required.

This phase is optional and should not block the MVP.

### Tasks

Create:

```text
Dockerfile
compose.yaml
```

as appropriate.

The runtime image should contain:

- PHP runtime.
- Required PHP extensions.
- Ghostscript.
- Application source/runtime dependencies.

Depending on deployment strategy, web server and scheduler may run as separate services/processes.

---

## Do Not Overengineer

Do not add:

- Kubernetes.
- Message broker.
- Redis.
- Distributed workers.
- Multi-region storage.

for this MVP.

---

# 5. AI Agent Working Protocol

For each implementation phase, the agent should use this process.

## Step 1 — Inspect

Read:

```text
prd.md
plan-implementation.md
```

Inspect files relevant to the current phase.

---

## Step 2 — State the Phase Scope

Before editing, summarize internally or in the task output:

```text
Current phase:
Goal:
Files expected to change:
Features explicitly excluded:
```

This prevents scope drift.

---

## Step 3 — Implement

Make the smallest coherent changes necessary to satisfy the phase.

---

## Step 4 — Test

Run the smallest relevant test subset first.

Example:

```bash
php artisan test --filter=PdfCompressionServiceTest
```

Then run a broader suite if the change is stable.

---

## Step 5 — Review

Check:

- Security.
- Error handling.
- Naming.
- Unnecessary abstractions.
- PRD compliance.
- Whether future-phase work leaked into the current phase.

---

## Step 6 — Report and Stop

At the end of each phase, report:

```markdown
## Phase Completion Report

### Implemented
- ...

### Files Changed
- ...

### Tests
- `...` — PASS

### Known Limitations
- ...

### Next Phase
- Phase X — ...

### Status
COMPLETE
```

Then stop.

The next phase should begin only when explicitly requested.

---

# 6. Suggested Agent Prompts Per Phase

These prompts can be given to an AI coding agent individually.

---

## Prompt — Phase 0

```text
Implement Phase 0 from plan-implementation.md.

Read prd.md and plan-implementation.md first.

Only bootstrap Laravel + Livewire and verify the application/test/build environment.
Do not implement PDF upload, compression, Ghostscript integration, downloads, or cleanup.

Run the relevant tests/build after implementation.
Return a phase completion report and stop.
```

---

## Prompt — Phase 1

```text
Implement Phase 1 from plan-implementation.md.

Read prd.md and inspect the current repository first.

Implement only PDF compressor configuration and CompressionLevel domain/application type.
Do not implement upload UI or Ghostscript execution.

Add focused tests, run them, return a phase completion report, and stop.
```

---

## Prompt — Phase 2

```text
Implement Phase 2 from plan-implementation.md.

Build PdfCompressionService and its application-specific exception.
Use safe structured process execution and a configurable timeout.
Do not implement Livewire upload, download flow, or cleanup yet.

Add tests for the service behavior.
Run tests, report changed files and results, then stop.
```

---

## Prompt — Phase 3

```text
Implement Phase 3 from plan-implementation.md.

Create the Livewire PDF upload interface and validation.
Store uploads in application-controlled temporary storage using generated internal filenames.
Medium compression must be the default.

Do not implement the final download or cleanup mechanism.

Add Livewire/feature tests, run them, provide a phase completion report, and stop.
```

---

## Prompt — Phase 4

```text
Implement Phase 4 from plan-implementation.md.

Connect PdfCompressor to PdfCompressionService and display compression-result metadata.
Do not expose filesystem paths to the client.

Handle service failures with safe user-facing errors and server-side logging.

Add tests, run them, provide a phase completion report, and stop.
```

---

## Prompt — Phase 5

```text
Implement Phase 5 from plan-implementation.md.

Implement secure download of generated PDFs using an opaque identifier or another Laravel-native secure mechanism.
The client must not provide arbitrary filesystem paths.

Add security-oriented feature tests.
Run tests, provide a phase completion report, and stop.
```

---

## Prompt — Phase 6

```text
Implement Phase 6 from plan-implementation.md.

Add resetCompression behavior and temporary-file cleanup command/scheduling.
Cleanup must only operate inside application-controlled temporary PDF directories.

Add tests for cleanup and reset behavior.
Run tests, provide a phase completion report, and stop.
```

---

## Prompt — Phase 7

```text
Implement Phase 7 from plan-implementation.md.

Polish the existing one-page Livewire UI for usability and responsive behavior.
Do not introduce new product features.

Verify the UI workflow and run existing tests/build.
Provide a phase completion report and stop.
```

---

## Prompt — Phase 8

```text
Implement Phase 8 from plan-implementation.md.

Perform security/reliability hardening against the documented edge cases.
Only add guards or error-handling changes justified by the PRD and observed risks.

Do not add new product features.

Run relevant automated tests and document any manual test cases.
Provide a phase completion report and stop.
```

---

## Prompt — Phase 9

```text
Implement Phase 9 from plan-implementation.md.

Do not add new features.

Run the full test suite and production build, review every PRD acceptance criterion, fix release-blocking issues, and update README.md with setup/deployment requirements.

Provide the final release-readiness report and stop.
```

---

# 7. Definition of Done

The project is complete when:

1. Every mandatory acceptance criterion in `prd.md` is satisfied.
2. Automated tests pass.
3. A production frontend build succeeds.
4. Ghostscript dependency is documented.
5. Temporary files are automatically cleaned.
6. No arbitrary command arguments can originate from user input.
7. No arbitrary server files can be downloaded.
8. Internal errors and filesystem paths are not exposed to users.
9. The full user workflow works:

```text
Upload
→ Select Compression Level
→ Compress
→ View Result
→ Download
→ Reset
```

10. No out-of-scope features have been added merely for architectural completeness.
