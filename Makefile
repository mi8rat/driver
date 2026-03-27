# Makefile for Lightweight C CMS with SQLite

# Compiler
CC = gcc

# Libraries needed (SQLite)
LIBS = -lsqlite3

# Compiler flags
CFLAGS = -Wall -g

# -----------------------------
# Targets
# -----------------------------

# Default target: build the executable 'cms'
all:
	$(CC) main.c db.c -o cms $(CFLAGS) $(LIBS)
	@echo "Build complete! Run './cms' to start the CMS."

# Run the CMS directly (after building)
run: all
	./cms

# Clean the build (delete the executable)
clean:
	rm -f cms
	@echo "Clean complete! Executable removed."
