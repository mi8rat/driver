package database

import (
	"database/sql"
	"fmt"
	_ "modernc.org/sqlite"
)

type DB struct {
	*sql.DB
}

func Init(path string) (*DB, error) {
	db, err := sql.Open("sqlite", path)
	if err != nil {
		return nil, fmt.Errorf("open db: %w", err)
	}

	if err := createTables(db); err != nil {
		return nil, fmt.Errorf("create tables: %w", err)
	}

	return &DB{db}, nil
}

func createTables(db *sql.DB) error {
	_, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS posts (
			id        INTEGER PRIMARY KEY AUTOINCREMENT,
			title     TEXT NOT NULL,
			slug      TEXT NOT NULL UNIQUE,
			content   TEXT NOT NULL,
			published INTEGER DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
		);
	`)
	return err
}
