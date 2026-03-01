# =============================================================================
# Simple CMS — Makefile
# =============================================================================
# Usage:
#   make          → builds the project (default)
#   make run      → builds and starts the server
#   make dev      → hot-reload friendly dev mode (no compile step)
#   make clean    → wipes build output and local database
#
# Requirements:
#   - Go 1.21+  (https://go.dev/dl/)
#   - golangci-lint (only for `make lint`)
# =============================================================================


# -----------------------------------------------------------------------------
# Variables
# -----------------------------------------------------------------------------

# Name of the compiled binary that will be placed in $(BUILD_DIR)/
APP_NAME  = cms

# Output directory for compiled binaries.
# Keeping binaries separate from source makes .gitignore and clean-up easier.
BUILD_DIR = bin

# Path to the main package (the folder that contains main.go).
# Using a subdirectory (cmd/) follows Go project layout conventions and allows
# multiple binaries to live in the same repo (e.g. cmd/server, cmd/cli).
CMD_DIR   = ./cmd


# -----------------------------------------------------------------------------
# Declare phony targets
# -----------------------------------------------------------------------------
# .PHONY tells Make that these target names are not real files on disk.
# Without this, if a file named "build" ever existed, `make build` would think
# the target is already up-to-date and do nothing.
.PHONY: all build run dev clean test fmt lint help


# =============================================================================
# Targets
# =============================================================================

# -----------------------------------------------------------------------------
# all — Default target (runs when you type just `make`)
# -----------------------------------------------------------------------------
# By convention, the first target in a Makefile is the default.
# We alias it to `build` so a bare `make` compiles the project.
all: build


# -----------------------------------------------------------------------------
# deps — Fetch and tidy Go module dependencies
# -----------------------------------------------------------------------------
# `go mod tidy` does two things:
#   1. Downloads any missing modules listed in go.mod into the local cache.
#   2. Removes any entries in go.mod / go.sum that are no longer imported.
# Running this before every build ensures a reproducible, minimal dependency
# graph and prevents "module not found" errors on a fresh clone.
deps:
	go mod tidy


# -----------------------------------------------------------------------------
# build — Compile the Go binary
# -----------------------------------------------------------------------------
# Depends on `deps` so dependencies are always up-to-date before compilation.
#
# Flags explained:
#   -o $(BUILD_DIR)/$(APP_NAME)   → place the binary at bin/cms
#   $(CMD_DIR)                    → compile the package at ./cmd
#
# The `@` prefix suppresses echoing the command itself, keeping output clean.
# We echo a friendly message instead so the developer knows what was produced.
build: deps
	@mkdir -p $(BUILD_DIR)
	go build -o $(BUILD_DIR)/$(APP_NAME) $(CMD_DIR)
	@echo "✅ Built: $(BUILD_DIR)/$(APP_NAME)"


# -----------------------------------------------------------------------------
# run — Build the binary, then execute it
# -----------------------------------------------------------------------------
# Depends on `build` so you always run the latest compiled version.
# The server listens on http://localhost:8080 (configured in main.go).
run: build
	./$(BUILD_DIR)/$(APP_NAME)


# -----------------------------------------------------------------------------
# dev — Run the app directly with `go run` (no compile step)
# -----------------------------------------------------------------------------
# Useful during active development because:
#   • Faster iteration — no separate build step.
#   • Go's toolchain compiles and runs in a single command.
#
# Trade-off: slightly slower startup than a pre-compiled binary, and you
# cannot easily attach a debugger. For debugging, prefer `make build` + dlv.
#
# Pair with a file-watcher (e.g. `air`, `reflex`) for true hot-reload:
#   air -c .air.toml
dev:
	go run $(CMD_DIR)/main.go


# -----------------------------------------------------------------------------
# test — Run the full test suite
# -----------------------------------------------------------------------------
# `./...` is a Go wildcard that matches all packages in the module recursively.
# `-v` enables verbose output so you see each test name and its pass/fail state.
#
# To run a single package:    go test ./internal/models/... -v
# To run a specific test:     go test ./... -run TestCreatePost -v
# To see test coverage:       go test ./... -cover
test:
	go test ./... -v


# -----------------------------------------------------------------------------
# fmt — Auto-format all Go source files
# -----------------------------------------------------------------------------
# `go fmt` enforces the canonical Go style (tabs for indentation, etc.).
# Run this before committing to keep diffs clean and avoid style nits in review.
# Most editors can be configured to run this on save via gopls / gofmt.
fmt:
	go fmt ./...


# -----------------------------------------------------------------------------
# lint — Run static analysis with golangci-lint
# -----------------------------------------------------------------------------
# golangci-lint aggregates many linters (staticcheck, errcheck, gosimple, etc.)
# in a single fast run. Install it from: https://golangci-lint.run/usage/install/
#
# Common installation:
#   brew install golangci-lint          (macOS)
#   go install github.com/golangci/golangci-lint/cmd/golangci-lint@latest
#
# To customise which linters run, add a .golangci.yml to the project root.
lint:
	golangci-lint run ./...


# -----------------------------------------------------------------------------
# clean — Remove generated files and reset local state
# -----------------------------------------------------------------------------
# Deletes:
#   $(BUILD_DIR)  → compiled binaries (bin/)
#   cms.db        → local SQLite database created at runtime
#
# Use this to start fresh or before a release build to avoid stale artifacts.
clean:
	@rm -rf $(BUILD_DIR) cms.db
	@echo "🧹 Cleaned build artifacts and database"


# -----------------------------------------------------------------------------
# help — Print a quick reference of all available targets
# -----------------------------------------------------------------------------
# A self-documenting help target is a Makefile best practice, especially on
# larger projects where contributors may not read the full file.
help:
	@echo ""
	@echo "┌─────────────────────────────────────────────┐"
	@echo "│           Simple CMS — Make Targets          │"
	@echo "└─────────────────────────────────────────────┘"
	@echo ""
	@echo "  make / make all   Build the binary (default)"
	@echo "  make deps         Download & tidy Go modules"
	@echo "  make build        Compile the application"
	@echo "  make run          Build then start the server"
	@echo "  make dev          Run via go run (no compile)"
	@echo "  make test         Run all tests with -v"
	@echo "  make fmt          Auto-format all Go files"
	@echo "  make lint         Run golangci-lint"
	@echo "  make clean        Remove bin/ and cms.db"
	@echo "  make help         Show this message"
	@echo ""
	@echo "  Server runs at → http://localhost:8080"
	@echo ""
