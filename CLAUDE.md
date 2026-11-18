<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.27
- laravel/framework (LARAVEL) - v13
- laravel/boost (BOOST) - v2
- laravel/prompts (PROMPTS) - v0
- laravel/tinker (TINKER) - v3
- livewire/livewire (LIVEWIRE) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v12

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain. Don't wait until you're stuck.

- `livewire-development`: Develops reactive Livewire 3 components. Activates when creating, updating, or modifying Livewire components; working with wire:model, wire:click, wire:loading, or any wire: directives; adding real-time updates, loading states, or reactivity; debugging component behavior; writing Livewire tests; or when the user mentions Livewire, component, counter, or reactive UI.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.
- If `.ai/rules/index.md` exists, consult it before editing a file and follow the project rules that match the file's path.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- Only create new documentation files if explicitly requested by the user. Keeping `docs/openapi.yaml` and the `## Features` section of `README.md` aligned with the code is mandatory project policy (see the BeeAPro guidelines below) and is not restricted by this rule.

## Replies

- Be concise in your explanations. Focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Run `php artisan list` to see the available Artisan commands and `php artisan <command> --help` to double-check the available parameters before calling one.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Debugging

- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.
- Use the `last-error` and `read-log-entries` tools to inspect application errors before guessing at a cause.
- When you need to execute PHP to debug code or query Eloquent models directly, run `php artisan tinker --execute "..."`.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful. Ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands with `php artisan list`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v13 rules ===

# Laravel 13

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- This project uses the streamlined file structure introduced in Laravel 11. Laravel 13 keeps it unchanged.

## Laravel 13 Structure

- Middleware are not registered in `app/Http/Kernel.php`. They are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- There is no `app\Console\Kernel.php`. Use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Commands auto-register. Files in `app/Console/Commands/` are automatically available and do not require manual registration.

## Laravel 13 Changes That Affect Generated Code

- CSRF: the middleware is `Illuminate\Foundation\Http\Middleware\PreventRequestForgery`. Do not reference `VerifyCsrfToken` or `ValidateCsrfToken` in new code or tests; they are deprecated aliases.
- Cache: only arrays and scalars may be cached unless a class is allow-listed in `cache.serializable_classes`. Prefer caching array payloads over Eloquent models or DTO objects.
- Never instantiate a model inside its own `boot()` / `booted()` / `boot*` trait methods; it throws a `LogicException`.
- `upsert()` requires a non-empty `uniqueBy` argument.
- Queue events: use `JobAttempted::$exception` (not `$exceptionOccurred`) and `QueueBusy::$connectionName` (not `$connection`).
- Use `Arr::first()` / `Arr::last()` instead of defining or relying on global `array_first()` / `array_last()` helpers.
- Pagination views: `pagination::bootstrap-3` and `pagination::simple-bootstrap-3` replace `pagination::default` and `pagination::simple-default`.
- Laravel 13 supports PHP attributes for model, job, and command configuration (for example `#[Table]`, `#[Fillable]`, `#[Tries]`, `#[Signature]`). Follow whichever style the sibling files already use; do not convert existing classes without approval.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Eagerly loaded records can be limited natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces using only PHP, no JavaScript required.
- Instead of writing frontend code in JavaScript frameworks, you use Alpine.js to build the UI when client-side interactions are required.
- State lives on the server; the UI reflects it. Validate and authorize in actions (they're like HTTP requests).
- IMPORTANT: Activate `livewire-development` every time you're working with Livewire-related tasks.

=== pint/core rules ===

# Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit 12 for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- PHPUnit 12 does not read doc-comment annotations. Use attributes (`#[Test]`, `#[DataProvider('name')]`, `#[Group('name')]`), never `@test`, `@dataProvider`, or `@group`.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).
</laravel-boost-guidelines>

<beeapro-guidelines>
# Development guidelines

1. Before starting the working session, checkout to `main` (our production branch), create a new branch and start
   developing your feature or bug fix
2. Branch names **MUST** use this format **"issue#-short-description"** where issue# is the number of the issue (ex.
   948-update-readme-on-reviews). Github will suggest you a branch name, please keep it shorter as possible.
3. Use descriptive commits (**why are you committing**), the format must be "#issueN procedure message". Procedure could
   be Amarea Policy crud, quotation, email controller, etc. Message doesn't matter the format for now, describe why are
   you commiting.
4. Make pull requests on their respective branches. They have to be logically consistent. For small issues, one PR is
   enough, but for multi-elements issues, let's make separate PRs, also based on each other
5. PR directions are issue-branch -> main -> devlg. Link the PR with the issue. Add the label "review" in the issue.
7. PR must be peer reviewed. Developer must assign a reviewer and must assign himself/herself to the PR as assignee. The
   reviewer must understand the issue requirements, review the code to have at least 2 persons that have seen the code.
   Moreover reviewer must check: REUSE what already developed, use of Laravel features, check the code is using our
   standard.
8. Once review is completed, the PR can be merged on main (both reviewer and owner can merge).
9. Since merging will close the issue, please **re-open it**
10. After the merge, the new version of main must be deployed on devlg. This is developer responsability.
11. After that the person who merged must ask customer experiance team (ex. Nermeen or Cristian) to test the new
    feature, tagging the tester in the Issue and adding label review:nermeen or review:cristian and assigning the issue
    to them.
12. Once user review is done (by for example Cristian and Nermeen), they need to label "USER REVIEW APPROVED"
13. Only product manager (Gian at the moment) can close the issue
14. Use Laravel components as much as possible.
15. If the need appears to download some new package, let's first discuss it. Packages are welcome, but we need to check
    if are well mantained.
16. **IMPORTANT**: when you have completed an issue, PR, verify, merge, remove branch and **TEST**
    that you have achieved the requirements of the issue. Then add label **"review"** on your issue.
17. If something application wide could be activated or disactivated use a systemVariable
18. If something on user base could be enabled or not use role permissions
19. Any user feature, must have also it's corresponding API call. Each API call must have a corresponding console
    command.
  - API must be documented using OpenAPI specifications
19b. **CRUD ↔ API parity for resources**:
  - Every CRUD action (C/R/U/D) must exist as an API action for the same resource.
  - Enforce the same RBAC rules in both UI and API (same policies/permissions).
20. **TEST, TEST and TEST**. Write unit tests for your methods. Write feature tests. Highlight the data structure needed
    in the test (and then in the methods).
21. **COMMENT** your code. At least explain what is the function goal and what is expected as data input or data output
22. Keep your functions short, no more than 20 lines
23. Ask your team if a method already exists for what you have to implement
24. Use boyscout principle: if you see a part of code that smells, if it's a small refactor made it. If it's a big one,
    open an issue with refactor label and assign it to PM, for approval.
25. Avoid long ifs and switches. Better to use **declarative structures** where you map data

For example

**NO**
```
switch ($categoria) {
    case 'rca':
        $payload['MTPLsel'] = 'Yes';
        break;
    case 'assistenza':
        $payload['Assisel'] = 'Yes';
        break;
    case 'infortuni':
        $payload['Pasel'] = 'Yes';
        break;
    case 'cvt':
        if ($code === 'WKM02') {
            $payload['FTsel'] = 'Yes';
        }
        if ($code === 'WKM03') {
            $payload['CollKasko'] = 'Full Kasko';
        }
        if ($code === 'WKM16') {
            $payload['Cbsel'] = 'Yes';
        }
        break;
}
```

**YES**
```
$rules = [
    'rca'         => ['MTPLsel' => 'Yes'],
    'assistenza'  => ['Assisel' => 'Yes'],
    'infortuni'   => ['Pasel'   => 'Yes'],
    'cvt' => [
        'WKM02' => ['FTsel'      => 'Yes'],
        'WKM03' => ['CollKasko'  => 'Full Kasko'],
        'WKM16' => ['Cbsel'      => 'Yes'],
    ],
];

if (isset($rules[$categoria])) {
    $catRules = $rules[$categoria];

    if ($categoria === 'cvt' && isset($catRules[$code])) {
        // Apply specific rule for this code
        $payload = array_merge($payload, $catRules[$code]);
    } elseif ($categoria !== 'cvt') {
        // Apply generic rule for this category
        $payload = array_merge($payload, $catRules);
    }
}
```

If the above is not clear for you, remember our 10 commandments

1. write small functions (maximum 20 lines),
2. choose a name that advice on what the function will return,
3. choose variable name that advice on which data is contained,
4. document functions with comments on what expected in input and what returned in output,
5. type hint parameters and return values,
6. Each Edge Function should have a single, clear purpose
7. Avoid making them too large or handling too many different operations
8. move business logic in edge functions
9. Always return proper HTTP status codes
10. Use try-catch blocks and return meaningful error messages
11. Log errors for debugging
12. write tests,
13. if test call external service, once you are ok with the response, mock it

## API Contract & OpenAPI Documentation (MANDATORY)

### 1. Single Source of Truth

All API endpoints MUST be documented in an OpenAPI 3.x specification file.

- Location: /docs/openapi.yaml
- The OpenAPI file is the single source of truth for:
    - Endpoints
    - Request payloads
    - Response formats
    - Error structures
    - Authentication
    - RBAC requirements

No API route may exist without being documented in OpenAPI.

---

### 2. CRUD ↔ API ↔ OpenAPI Consistency

Whenever:
- A CRUD feature is created
- An API endpoint is added or modified
- A request/response structure changes
- RBAC rules change

The OpenAPI specification MUST be updated in the same PR.

PRs without updated OpenAPI documentation must not be approved.

---

### 3. Versioning

All APIs must be versioned:

/api/v1/...
/api/v2/...

Breaking changes require a new version.
OpenAPI must clearly separate versions.

---

### 4. Standard Response Structure

All APIs must follow a consistent JSON structure for:

- Success responses
- Validation errors
- Authorization errors
- Server errors

The structure must be documented in OpenAPI and validated in Feature tests.

---

### 5. Test Alignment

Feature tests are the implementation validator.
OpenAPI is the contract definition.

Examples defined in OpenAPI must match real test payloads.

If tests and documentation diverge, tests win and documentation must be fixed immediately.

---

### 6. Deprecation Policy

If an endpoint is deprecated:

- Mark it as deprecated in OpenAPI.
- Provide migration notes.
- Maintain compatibility for at least one version cycle.

## README Feature List (MANDATORY)

`README.md` contains a `## Features` section that is the human-readable inventory of what the
platform does. It must stay aligned with the code.

Whenever a PR:

- Adds a user-facing feature
- Removes or retires a user-facing feature
- Materially changes how an existing feature behaves

the `## Features` section of `README.md` MUST be updated in the same PR.

Rules:

- One subsection per feature, following the existing heading style (`### Feature Name`).
- Describe what the user can do, not how it is implemented.
- When a feature is retired, delete its subsection — do not leave it describing a dead flow.
- Placeholder or disabled UI must not be listed as a feature until it actually works.

PRs that change user-facing behaviour without updating the feature list must not be approved.

---

## Autofill best practices

How Autofill Must Work (Fetch Data on Front-End)
Use events onChange or onInput with debounce (300-500ms). Do NOT use onBlur.
This ensures a smoother user experience by waiting for user input to stabilize before sending a request.

Check that the value has changed compared to the previous one.
Avoid making unnecessary API calls if the user enters the same value as before.

Use memoization to cache data and avoid making the same request twice.
Store previously fetched results for specific inputs, reducing redundant calls to the API.

Abort previous requests.
Since there is no guarantee of the sequence of responses from the server, make sure to cancel any ongoing request if a
new one is initiated. This avoids race conditions.

Add a spinner or loader to show progress to the user, especially when filling dependent fields.
Display a loading indicator near the input or dependent fields being updated, so the user knows data is being fetched.

Disable the save button while a request is in progress.
Prevent users from saving incomplete or inconsistent data while waiting for the response.

If the user clears the input, clear the corresponding fields.
Ensure any dependent or autofilled fields are cleared when the input is emptied. This prevents saving incorrect or
outdated data.

## TESTING

New feature: Start coding from testing. Write your test strategy and then write test. Then you can write the feature,
that must pass your test.
Refactoring: Check if test already exists. Make sure passes. Then refactor and check again test.

REALLY IMPORTANT: Write your test strategy!!

### Always use factories or fixtures in tests

Preparing test dataset is really important in out test. You have to decide which data you want to load to have a fully
functional test, consistent with your test strategy.

### IDEMPOTENCY

Idempotency refers to the ability of an operation to produce the same result no matter how many times it is executed. In
testing, this ensures that repeated actions on a system do not introduce unintended changes or side effects.

For example, a `PUT` API request to update a resource with the same data is idempotent because it always results in the
same state. In contrast, a `POST` request to create a new resource is typically non-idempotent, as repeated executions
might create duplicate entries.

#### Why It Matters

- **Predictable Results:** Ensures the system behaves consistently under repeated operations.
- **Safe Retries:** Allows systems to handle network failures or retries without unexpected outcomes.
- **Reliable Automation:** Guarantees stability in automated test runs, even when repeated.

#### Testing Idempotency

1. Perform the operation multiple times with the same input.
2. Verify that the result and system state remain consistent.
3. Simulate retries or network failures to ensure the operation handles them gracefully.

Idempotency is crucial for building resilient and reliable systems, especially in distributed or API-driven
environments.

### Fake or Mock external services

To avoid test dependency on external services, each service used in test should be **faked** using Http::fake (better) or mocked using Mock::
</beeapro-guidelines>

<domain_specifications>
# Domain: Microlearning Course Platform

## Overview

The platform delivers bite-sized educational content organized into **courses** and **lessons**. Each course is a self-contained learning unit with structured content, assessments, and optional interactive games. Content follows a JSON specification (currently versioned as `"1.0"`).

## Core Concepts

### Course

A course is the top-level container representing a complete learning topic. It includes metadata such as a title, plain-text description, category (e.g., "Sales", "Compliance", "Onboarding"), difficulty level (`beginner`, `intermediate`, or `advanced`), and language. Courses may optionally have an associated image and/or video. A course contains one or more lessons and may also reference course-level documents.

### Lesson

A lesson is a single, focused micro-unit of learning within a course. Lessons are ordered by `orderIndex` (starting at 1) and should be completable in 1–10 minutes (`estimatedMinutes`). Each lesson has:

- A **title** and short **description**.
- **detailedContent**: the main instructional body, formatted as HTML.
- **tags**: an array of keyword strings for categorization and search.
- **documents**: one or more attached reference files (each with a `fileName` and `filePath`). At least one document is required.
- **questions**: assessment questions (0–10). A lesson may legitimately have none when the course author scopes questions to specific lessons (e.g. only a closing feedback lesson carries them).
- **games** (optional): interactive activities like drag-and-drop, sorting, or labeling exercises.
- An optional **videoUrl** for embedded video content.

### Document

A document is an attached file referenced by a lesson or by the course itself. It has a `fileName` (display name) and a `filePath` (location or URL of the file). Documents are required at the lesson level (at least one per lesson) and optional at the course level.

### Question

A question is a quiz or assessment item within a lesson. Each question has a `title`, `questionText` (the prompt shown to the learner), a `kind`, and an `orderIndex`. Every question must have at least 2 answers.

`kind` is either `"quiz"` (default) or `"survey"`. A **quiz** question must have **at least one answer marked correct** (`isCorrect: true`). A **survey** question — opinion, feedback or satisfaction — has no correct answer, so every option carries `isCorrect: false` and the correct-answer rule is waived.

### Answer

An answer is a single option within a question. It has display `text`, a boolean `isCorrect` flag, and an `orderIndex`. Multiple answers can be marked correct (supporting multi-select questions).

### Game

A game is an optional interactive exercise attached to a lesson. Each game has a `title`, `instructions`, a `type` (one of `drag_drop`, `sorting`, or `labeling`), and a freeform `config` object that defines the game's content and behavior. The structure of `config` varies by game type.

## Key Constraints

- Lesson `estimatedMinutes` must be between 1 and 10 (microlearning principle).
- Every lesson must have at least 1 document.
- A lesson may have 0 to 10 questions; 0 is valid when questions are deliberately scoped to other lessons.
- Questions must have at least 2 answers. Quiz questions need at least one correct answer; survey questions have none.
- `orderIndex` fields are 1-based integers used for sequencing lessons, questions, and answers.
- Course `description` is plain text only — no HTML or markdown.
- Lesson `detailedContent` is HTML.
- `additionalProperties` is `false` at every level — do not invent fields outside the schema.
</domain_specifications>

<storage_specifications>
# Storage: SQLite (Demo)

## Decision and scope

- This is a demo. The only database is a single SQLite file on the local filesystem. There is no database server.
- All Boost and BeeAPro rules about Eloquent, relationships, eager loading, factories, seeders, and migrations apply unchanged.
- Designed for one server and low traffic. Replication, read replicas, and multi-server deployments are non-goals.

## Connection

- `DB_CONNECTION=sqlite`, database file at `database/database.sqlite`. The file and its `-wal` / `-shm` companions are git-ignored.
- In `config/database.php` the `sqlite` connection must have `foreign_key_constraints` enabled, `journal_mode` set to `wal`, and a `busy_timeout` of 5000 ms.
- Sessions, cache, and queue use the `database` driver on the same file. Do not introduce Redis or any other service.
- Backup and restore are a copy of the database file together with the `storage/app` folder, taken while the application is stopped.

## Portability (mandatory)

Moving to MySQL or PostgreSQL later must require only a change of environment variables and a fresh migration.

- No SQLite-specific SQL: no `PRAGMA`, `strftime`, `json_extract`, `rowid`, or other raw expressions. Use Eloquent and the query builder.
- No raw SQL in migrations. Use the schema builder only.
- Do not rely on SQLite's loose typing. Declare real column types and cast in the model's `casts()` method.
- Do not rely on SQLite's case-insensitive `LIKE` for search behaviour that tests assert on.
- JSON columns are read and written through Eloquent casts only, never queried with JSON path operators.

## Schema

The Microlearning Course Specification is stored relationally. Column names are snake_case versions of the specification fields (`orderIndex` becomes `order_index`).

| Table       | Belongs to                 | Notes |
|-------------|----------------------------|-------|
| `courses`   |                            | title, description, category, level, language, image_url, video_url, spec_version |
| `lessons`   | course                     | detailed_content, order_index, tags (JSON), estimated_minutes, video_url |
| `documents` | course or lesson (morph)   | file_name, file_path |
| `questions` | lesson                     | title, question_text, kind, order_index |
| `answers`   | question                   | text, is_correct, order_index |
| `games`     | lesson                     | title, type, instructions, config (JSON) |

- `level`, `kind`, and game `type` are backed by PHP enums with TitleCase cases and cast on the model.
- Foreign keys cascade on delete from course down to answers, games, and documents.
- `order_index` is unique within its parent (for example `lessons.course_id` + `order_index`).
- The specification constraints (1 to 10 minutes, at least 1 document per lesson, 0 to 10 questions, at least 2 answers, quiz versus survey correctness) are enforced in Form Requests and in the import action, not by database triggers.

## Migrations on SQLite

- Dropping or altering a foreign key on an existing table is not supported by SQLite. Define foreign keys when the table is created; if one must change in the demo, change the original migration and run `php artisan migrate:fresh --seed`.
- When modifying a column, repeat every attribute previously defined on it.

## JSON import and export

- A course JSON document (version `"1.0"`) is imported into these tables inside a single database transaction: either the whole course is stored or nothing is.
- Export rebuilds a document that validates against the specification, ordered by `order_index`, with no extra fields.
- Import followed by export of the same document must return an equivalent document. This round trip must be tested.

## Uploaded files

- Files referenced by `documents.file_path` live on the `local` disk under `storage/app/private/course-documents/{course_id}/`. External URLs are stored as given.
- Deleting a course deletes its folder.

## Demo data

- Seeders create the demo courses from JSON documents in `database/seeders/data/*.json` through the same import action used by the application.
- `php artisan migrate:fresh --seed` is the reset command for the demo.

## Testing

- Tests run on SQLite in memory (`DB_DATABASE=:memory:` in `phpunit.xml`) with the `RefreshDatabase` trait. No test touches `database/database.sqlite`.
- Test data comes from model factories. Uploaded files use `Storage::fake('local')`.
- Required cases beyond CRUD: import is atomic on invalid input, import/export round trip, cascade on course delete, uniqueness of `order_index` within a parent.
</storage_specifications>

<course-plan>
# Microlearning Course Generation Guide

You are a microlearning course generator. Your task is to produce a valid JSON object that conforms to the Microlearning Course Specification. Follow this guide precisely.

---

## Output Structure

Your output must be a single JSON object with the following top-level keys:

```
{
  "version": "1.0",
  "course": { ... },
  "lessons": [ ... ],
  "courseDocuments": [ ... ]   // optional
}
```

---

## 1. `version`

- Always set to `"1.0"`.
- Format: string matching the pattern `X.Y` (e.g., `"1.0"`, `"2.1"`).

---

## 2. `course`

The course object describes the overall learning topic.

| Field         | Type   | Required | Rules                                                                 |
|---------------|--------|----------|-----------------------------------------------------------------------|
| `title`       | string | Yes      | Minimum 3 characters. Clear and descriptive.                          |
| `description` | string | Yes      | Plain text only. No HTML, no markdown. Summarize what the learner will gain. |
| `category`    | string | Yes      | A broad topic area (e.g., "Sales", "Compliance", "Leadership", "IT"). |
| `level`       | string | Yes      | One of: `"beginner"`, `"intermediate"`, `"advanced"`.                 |
| `language`    | string | Yes      | Language code or name (e.g., `"en"`, `"English"`, `"es"`).            |
| `imageUrl`    | string | No       | A valid URI pointing to a course cover image.                         |
| `videoUrl`    | string | No       | A valid URI pointing to an intro video.                               |

**No additional fields are allowed.**

### Example

```json
{
  "title": "Introduction to Workplace Safety",
  "description": "Learn the fundamentals of workplace safety including hazard identification, emergency procedures, and personal protective equipment.",
  "category": "Compliance",
  "level": "beginner",
  "language": "en"
}
```

---

## 3. `lessons`

An array of lesson objects. **At least 1 lesson is required.** Each lesson is a focused, bite-sized learning unit.

| Field              | Type     | Required | Rules                                                                                  |
|--------------------|----------|----------|----------------------------------------------------------------------------------------|
| `title`            | string   | Yes      | Minimum 3 characters. Descriptive of the lesson topic.                                 |
| `description`      | string   | Yes      | Brief summary of the lesson content.                                                   |
| `detailedContent`  | string   | Yes      | **HTML formatted** instructional content. This is the main body the learner reads.      |
| `orderIndex`       | integer  | Yes      | Starts at 1 and increments sequentially across lessons.                                |
| `tags`             | array    | Yes      | Array of keyword strings relevant to the lesson (can be empty `[]`).                   |
| `estimatedMinutes` | integer  | Yes      | Between 1 and 10 (inclusive). Reflects microlearning — keep it short.                   |
| `videoUrl`         | string   | No       | A valid URI to a lesson-specific video.                                                |
| `documents`        | array    | Yes      | **At least 1 document required.** See [Document](#4-document) section.                 |
| `questions`        | array    | Yes      | **Between 0 and 10 questions** (use `[]` when this lesson carries none). See [Question](#5-question) section.                   |
| `games`            | array    | No       | Optional interactive activities. See [Game](#7-game) section.                          |

**No additional fields are allowed.**

### Guidelines for `detailedContent`

- Use valid HTML tags: `<h2>`, `<h3>`, `<p>`, `<ul>`, `<ol>`, `<li>`, `<strong>`, `<em>`, `<table>`, `<tr>`, `<td>`, `<th>`, etc.
- Structure content with headings, paragraphs, and lists for readability.
- Keep content concise and aligned with the `estimatedMinutes` value.
- Do not use `<script>`, `<style>`, or any executable content.

### Example

```json
{
  "title": "Identifying Workplace Hazards",
  "description": "Learn to recognize common physical, chemical, and ergonomic hazards in the workplace.",
  "detailedContent": "<h2>What Is a Workplace Hazard?</h2><p>A workplace hazard is any source of potential damage, harm, or adverse effect on a person's health or safety.</p><h3>Types of Hazards</h3><ul><li><strong>Physical:</strong> Slippery floors, exposed wiring, loud machinery.</li><li><strong>Chemical:</strong> Exposure to cleaning agents, fumes, or solvents.</li><li><strong>Ergonomic:</strong> Poor workstation setup, repetitive motions.</li></ul><p>Always report identified hazards to your supervisor immediately.</p>",
  "orderIndex": 1,
  "tags": ["safety", "hazards", "workplace"],
  "estimatedMinutes": 4,
  "documents": [ ... ],
  "questions": [ ... ]
}
```

---

## 4. `document`

A reference file attached to a lesson or to the course. Used in both `lesson.documents` and `courseDocuments`.

| Field      | Type   | Required | Rules                                                        |
|------------|--------|----------|--------------------------------------------------------------|
| `fileName` | string | Yes      | Minimum 1 character. The display name (e.g., `"Safety Guide.pdf"`). |
| `filePath` | string | Yes      | Minimum 1 character. The file location or URL.               |

**No additional fields are allowed.**

### Example

```json
{
  "fileName": "Hazard Identification Checklist.pdf",
  "filePath": "/documents/hazard-checklist.pdf"
}
```

---

## 5. `question`

An assessment item within a lesson. Tests the learner's understanding.

| Field          | Type    | Required | Rules                                                                          |
|----------------|---------|----------|--------------------------------------------------------------------------------|
| `title`        | string  | Yes      | A short label for the question (e.g., `"Hazard Types"`).                       |
| `questionText` | string  | Yes      | The full question prompt shown to the learner.                                 |
| `kind`         | string  | Yes      | One of: `"quiz"` (default) or `"survey"`.                                      |
| `orderIndex`   | integer | Yes      | Starts at 1 and increments sequentially within the lesson.                     |
| `answers`      | array   | Yes      | **At least 2 answers.** A `quiz` needs at least one `isCorrect: true`; a `survey` has none. See [Answer](#6-answer). |

**No additional fields are allowed.**

### Important Rules

- Every **quiz** question must have at least one correct answer.
- A **survey** question (`kind: "survey"`) captures an opinion — satisfaction, feedback, a
  recommendation — so no option is correct and every answer carries `isCorrect: false`.
- Questions can support multi-select (multiple `isCorrect: true` answers).
- Write clear, unambiguous question text.
- Avoid trick questions — the goal is to reinforce learning.
- When the course author supplies question or answer wording, reproduce it **verbatim** — never
  paraphrase, translate, or substitute questions of your own.

### Example

```json
{
  "title": "Hazard Types",
  "questionText": "Which of the following is an example of an ergonomic hazard?",
  "kind": "quiz",
  "orderIndex": 1,
  "answers": [
    { "text": "A wet floor without a warning sign", "isCorrect": false, "orderIndex": 1 },
    { "text": "Prolonged use of a poorly adjusted chair", "isCorrect": true, "orderIndex": 2 },
    { "text": "Exposure to chemical fumes", "isCorrect": false, "orderIndex": 3 },
    { "text": "Loud machinery without ear protection", "isCorrect": false, "orderIndex": 4 }
  ]
}
```

---

## 6. `answer`

A single answer option within a question.

| Field        | Type    | Required | Rules                                                    |
|--------------|---------|----------|----------------------------------------------------------|
| `text`       | string  | Yes      | Minimum 1 character. The answer text shown to the learner. |
| `isCorrect`  | boolean | Yes      | `true` if this is a correct answer, `false` otherwise.    |
| `orderIndex` | integer | Yes      | Starts at 1 and increments sequentially within the question. |

**No additional fields are allowed.**

---

## 7. `game` (Optional)

An interactive exercise attached to a lesson to reinforce learning through engagement.

| Field          | Type   | Required | Rules                                                                  |
|----------------|--------|----------|------------------------------------------------------------------------|
| `title`        | string | Yes      | Name of the game activity.                                             |
| `type`         | string | Yes      | One of: `"drag_drop"`, `"sorting"`, `"labeling"`.                      |
| `instructions` | string | Yes      | Clear directions for the learner on how to complete the activity.       |
| `config`       | object | Yes      | A freeform object defining the game content. Structure varies by type. |

**No additional fields are allowed.**

### Config Suggestions by Type

**`drag_drop`** — Learner drags items to matching targets:

```json
{
  "config": {
    "pairs": [
      { "item": "Wet floor", "target": "Physical Hazard" },
      { "item": "Chemical fumes", "target": "Chemical Hazard" },
      { "item": "Bad posture", "target": "Ergonomic Hazard" }
    ]
  }
}
```

**`sorting`** — Learner sorts items into the correct order or categories:

```json
{
  "config": {
    "categories": ["Do First", "Do Second", "Do Third"],
    "items": [
      { "text": "Assess the situation", "correctCategory": "Do First" },
      { "text": "Call emergency services", "correctCategory": "Do Second" },
      { "text": "Administer first aid", "correctCategory": "Do Third" }
    ]
  }
}
```

**`labeling`** — Learner labels parts of a diagram or image:

```json
{
  "config": {
    "imageUrl": "/images/fire-extinguisher.png",
    "labels": [
      { "text": "Pressure Gauge", "x": 50, "y": 20 },
      { "text": "Nozzle", "x": 30, "y": 80 },
      { "text": "Pin", "x": 55, "y": 10 }
    ]
  }
}
```

---

## 8. `courseDocuments` (Optional)

An array of documents attached at the course level (not tied to a specific lesson). Uses the same [Document](#4-document) structure.

---

## Generation Checklist

Before returning your JSON, verify:

- [ ] `version` is `"1.0"`.
- [ ] `course` has all 5 required fields (`title`, `description`, `category`, `level`, `language`).
- [ ] `course.description` contains **no HTML or markdown**.
- [ ] `course.level` is one of `"beginner"`, `"intermediate"`, `"advanced"`.
- [ ] `lessons` array has **at least 1** lesson.
- [ ] Each lesson has all 8 required fields.
- [ ] Each lesson `orderIndex` starts at 1 and increments sequentially.
- [ ] Each lesson `estimatedMinutes` is between 1 and 10.
- [ ] Each lesson `detailedContent` is valid HTML.
- [ ] Each lesson has **at least 1 document**.
- [ ] Each lesson has **between 0 and 10 questions**.
- [ ] Each question has **at least 2 answers**.
- [ ] Each question has a `kind` of `"quiz"` or `"survey"`.
- [ ] Each **quiz** question has **at least 1 correct answer** (`isCorrect: true`); **survey** questions have none.
- [ ] Each question and answer `orderIndex` starts at 1 and increments sequentially.
- [ ] `game.type` (if present) is one of `"drag_drop"`, `"sorting"`, `"labeling"`.
- [ ] **No extra fields exist** at any level — the schema is strict (`additionalProperties: false`).

---

## Full Minimal Example

```json
{
  "version": "1.0",
  "course": {
    "title": "Introduction to Workplace Safety",
    "description": "Learn the fundamentals of workplace safety including hazard identification, emergency procedures, and personal protective equipment.",
    "category": "Compliance",
    "level": "beginner",
    "language": "en"
  },
  "lessons": [
    {
      "title": "Identifying Workplace Hazards",
      "description": "Learn to recognize common physical, chemical, and ergonomic hazards.",
      "detailedContent": "<h2>What Is a Workplace Hazard?</h2><p>A workplace hazard is any source of potential damage, harm, or adverse effect on a person's health or safety.</p><h3>Types of Hazards</h3><ul><li><strong>Physical:</strong> Slippery floors, exposed wiring.</li><li><strong>Chemical:</strong> Fumes, solvents.</li><li><strong>Ergonomic:</strong> Poor workstation setup.</li></ul>",
      "orderIndex": 1,
      "tags": ["safety", "hazards"],
      "estimatedMinutes": 3,
      "documents": [
        {
          "fileName": "Hazard Checklist.pdf",
          "filePath": "/documents/hazard-checklist.pdf"
        }
      ],
      "questions": [
        {
          "title": "Ergonomic Hazard",
          "questionText": "Which of the following is an ergonomic hazard?",
          "kind": "quiz",
          "orderIndex": 1,
          "answers": [
            { "text": "A wet floor", "isCorrect": false, "orderIndex": 1 },
            { "text": "A poorly adjusted chair", "isCorrect": true, "orderIndex": 2 },
            { "text": "Chemical fumes", "isCorrect": false, "orderIndex": 3 }
          ]
        }
      ]
    }
  ]
}
```
</course-plan>
