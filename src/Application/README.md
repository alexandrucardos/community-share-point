# Application layer conventions

The Application layer is organized as **vertical slices**: one folder per use case,
holding the message, its handler, and (for queries) the read model it returns. CQS is
required — a slice is either a **command** (mutates state, returns nothing meaningful) or a
**query** (returns data, mutates nothing). Controllers are the only place allowed to both
mutate and return.

## Folder layout

```
src/Application/
└─ <UseCase>/            # one vertical slice per use case
    ├── <UseCase>Command.php   OR  <UseCase>Query.php
    ├── <UseCase>Handler.php
    └── <Name>View.php         # queries only: the read model returned
```

Invariant: **the folder name is the prefix of the files inside it.** `CreateItem/` contains
`CreateItemCommand` + `CreateItemHandler`; `ListUserItems/` contains `ListUserItemsQuery` +
`ListUserItemsHandler` + its view. If you can't name the folder after a single use case,
it's probably not one slice.

## Naming rules

### 1. One verb per intent

| Intent               | Verb     | Message type |
|----------------------|----------|--------------|
| Create new state     | `Create` | Command      |
| Mutate existing state| `Update` | Command      |
| Remove state         | `Delete` | Command      |
| Fetch one            | `Get`    | Query        |
| Fetch many           | `List`   | Query        |

Do not mix synonyms (`Add`/`Create`, `Load`/`Get`, `Fetch`/`Find`). Pick the verb from this
table so the name alone tells you what the slice does and whether it's a command or a query.

### 2. Message-type suffixes

- Commands end in `Command`, queries in `Query`, handlers in `Handler`.
- One handler per message, named after it (`CreateUserCommand` → `CreateUserHandler`).

### 3. Read models end in `View`

A query's return type is a `...View`, not a generic `...Dto` — the suffix states its role
(a read model shaped for a caller), not just "data bag". Name a **collection row in the
singular**: a query returning a list of a user's items returns `UserItemView[]`, so the file
is `UserItemView.php`, not `ListUserItemsDto.php`.

### 4. Descriptive over clever

In a vertical slice a long precise name beats a short ambiguous one:
`GetUserByEmailAndGroup` (states the lookup keys) is better than `GetUserByCredentials`
(hides which credentials).

## Ports

Repository interfaces and other contracts the Application layer depends on live under
interfaces — they are dependency-inversion boundaries. Infrastructure provides the implementations; the
Application layer speaks only to these interfaces.

## Boundaries (see /CLAUDE.md)

- Domain classes must not reference Application classes; Application classes must not
  reference Infrastructure classes. Depend on interfaces, never on concretions.
- Every object except Domain entities is **immutable** — commands, queries, and views are
  read-only value objects.

## Optional: grouping by aggregate

The slice folders may be grouped one level deeper by aggregate (`Item/CreateItem/`,
`User/GetUser/`) once the flat list grows past ~15 slices and scanning gets hard. Until
then, keep it flat. The naming rules above apply either way.
```
src/Application/
├── Item/
│   ├── CreateItem/  ├─ CreateItemCommand.php  └─ CreateItemHandler.php
│   ├── UpdateItem/
│   ├── ListGroupItems/  └─ GroupItemView.php
│   └── ListUserItems/   └─ UserItemView.php
└── User/
    ├── CreateUser/
    ├── UpdateUser/
    ├── GetUser/                └─ UserView.php
    └── GetUserByEmailAndGroup/ └─ UserCredentialsView.php
```
