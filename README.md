# 📝 Simple CMS

A lightweight Content Management System built with Go and SQLite. No frameworks, no fuss — just the Go standard library, a single SQLite database, and HTML templates.

---

## Features

- **Public blog** — displays all published posts at `/`
- **Single post view** — clean reading page at `/post/{slug}`
- **Admin dashboard** — manage all posts at `/admin`
- **Create & edit posts** — with title, content, and draft/publish toggle
- **Auto-slugging** — "Hello World" becomes `/post/hello-world` automatically
- **Draft support** — save posts privately before publishing
- **Zero external web frameworks** — uses only `net/http`
- **Pure Go SQLite** — no CGO required (`modernc.org/sqlite`)

---

## Requirements

- Go 1.21+ → https://go.dev/dl/
- `golangci-lint` _(optional, only for `make lint`)_ → https://golangci-lint.run/usage/install/

---

## Getting Started

```bash
# 1. Clone the repo
git clone https://github.com/yourname/cms.git
cd cms

# 2. Download dependencies
make deps

# 3. Build and run
make run
```

The server starts at **http://localhost:8080**

---

## Project Structure

```
cms/
├── Makefile                  # Build, run, test, lint targets
├── go.mod                    # Go module definition
├── go.sum                    # Dependency checksums
├── cms.db                    # SQLite database (auto-created on first run)
│
├── cmd/
│   └── main.go               # Entry point — sets up routes and starts server
│
├── internal/
│   ├── database/
│   │   └── database.go       # SQLite connection and table initialisation
│   ├── models/
│   │   └── post.go           # Post struct and all CRUD operations
│   └── handlers/
│       └── handlers.go       # HTTP request handlers
│
├── templates/
│   ├── base.html             # Shared layout (nav, header, footer)
│   ├── index.html            # Public post listing page
│   ├── post.html             # Single post reading view
│   ├── admin.html            # Admin dashboard with post table
│   └── form.html             # Create / edit post form
│
└── static/
    └── css/
        └── style.css         # All styles (no external CSS framework)
```

---

## Available Routes

| Method | Path                | Description              |
|--------|---------------------|--------------------------|
| GET    | `/`                 | Public post list         |
| GET    | `/post/{slug}`      | View a single post       |
| GET    | `/admin`            | Admin dashboard          |
| GET    | `/admin/new`        | New post form            |
| POST   | `/admin/new`        | Create a post            |
| GET    | `/admin/edit/{id}`  | Edit post form           |
| POST   | `/admin/edit/{id}`  | Update a post            |
| POST   | `/admin/delete/{id}`| Delete a post            |

---

## Make Targets

| Command       | Description                              |
|---------------|------------------------------------------|
| `make`        | Build the binary (default)               |
| `make deps`   | Download and tidy Go modules             |
| `make build`  | Compile the application                  |
| `make run`    | Build then start the server              |
| `make dev`    | Run via `go run` (no compile step)       |
| `make test`   | Run all tests with verbose output        |
| `make fmt`    | Auto-format all Go source files          |
| `make lint`   | Run `golangci-lint` static analysis      |
| `make clean`  | Remove `bin/` and `cms.db`              |
| `make help`   | Print all available targets              |

---

## Development Tips

**Quick iteration** — use `make dev` to skip the compile step during development:
```bash
make dev
```

**Hot reload** — pair `make dev` with [air](https://github.com/air-verse/air) for automatic restarts on file changes:
```bash
go install github.com/air-verse/air@latest
air
```

**Run a specific test:**
```bash
go test ./internal/models/... -run TestCreatePost -v
```

**Check test coverage:**
```bash
go test ./... -cover
```

---

## Database

The SQLite database (`cms.db`) is created automatically in the project root on first run. To reset it completely:

```bash
make clean
```

---

## License

MIT
