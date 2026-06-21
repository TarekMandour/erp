# TransactionsService Split — Migration Notes

## What changed

`TransactionsService.php` (992 lines, 41 methods) was split into 6 focused
classes under `App\Services\Finance\TransactionsService\*`, with the original
class now acting as a thin orchestrator that only contains `process()`,
`reverse()`, `regenerate()`, and `createFallbackJournalEntry()`.

| Class | Responsibility | File |
|---|---|---|
| `TransactionsService` | Public API / orchestration | `Services/Finance/TransactionsService.php` |
| `ScenarioResolver` | Detect operation type, resolve posting scenario | `TransactionsService/ScenarioResolver.php` |
| `PostingRuleEngine` | Fetch rules, calculate amounts, resolve accounts/cost centers | `TransactionsService/PostingRuleEngine.php` |
| `SafeFormulaEvaluator` | Safe (non-`eval`) math expression parsing | `TransactionsService/SafeFormulaEvaluator.php` |
| `JournalEntryBuilder` | Create entry headers, number entries, balance checks, account balance updates | `TransactionsService/JournalEntryBuilder.php` |
| `EntryDescriptionBuilder` | Human-readable Arabic descriptions for entries/items | `TransactionsService/EntryDescriptionBuilder.php` |
| `SourceModelHelper` | Read data off the source model, link/unlink journal entries | `TransactionsService/SourceModelHelper.php` |

## Dependency graph

```
TransactionsService
 ├── ScenarioResolver            (no deps)
 ├── PostingRuleEngine
 │    ├── SafeFormulaEvaluator   (no deps)
 │    └── EntryDescriptionBuilder (no deps)
 ├── JournalEntryBuilder
 │    ├── EntryDescriptionBuilder
 │    └── SourceModelHelper      (no deps)
 ├── EntryDescriptionBuilder
 └── SourceModelHelper
```

All dependencies are concrete classes with no interfaces and no constructor
arguments that need manual binding, so Laravel's service container will
autowire everything automatically — **no changes needed in any
ServiceProvider**.

## What callers need to know

**Nothing changes for existing callers.** The public methods
`process()`, `reverse()`, and `regenerate()` keep the exact same signatures
and behavior. If you resolve `TransactionsService` via the container
(`app(TransactionsService::class)`, constructor injection, or the service
container resolving it for you in a controller/job), it will keep working
as-is.

The only case that breaks is if any code did `new TransactionsService()`
directly with no arguments — that will now fail because the constructor
requires 5 dependencies. Search your codebase for:

```
new TransactionsService(
```

and replace with container resolution (`app(TransactionsService::class)`)
if you find any.

## Testing notes

Each new class can now be unit tested in isolation:

- `SafeFormulaEvaluator` — pure logic, zero DB/model dependencies. Easiest
  to get full coverage on (division by zero, invalid characters, nested
  parens, negative numbers).
- `ScenarioResolver` — mock `PostingScenario` queries / cache.
- `PostingRuleEngine` — inject a mocked `SafeFormulaEvaluator` and
  `EntryDescriptionBuilder` to test amount/account resolution in isolation
  from formula parsing.
- `TransactionsService` itself can now be tested by mocking all 5
  collaborators, so you can verify orchestration logic (e.g. "fallback is
  used when scenario is null") without touching real DB-backed rule
  evaluation.

## Nothing was changed behaviorally

This was a pure structural refactor — every method body is verbatim from
the original file (only `$this->xxx()` calls were rewritten to
`$this->collaborator->xxx()` where the method moved to a new class). A
method-name diff between the original and the split files confirms all 41
original methods are present exactly once, with only the 3 new
`__construct()` methods added.
