package models

import (
	"database/sql"
	"fmt"
	"regexp"
	"strings"
	"time"
)

type Post struct {
	ID        int
	Title     string
	Slug      string
	Content   string
	Published bool
	CreatedAt time.Time
	UpdatedAt time.Time
}

func Slugify(title string) string {
	re := regexp.MustCompile(`[^a-z0-9]+`)
	s := strings.ToLower(title)
	s = re.ReplaceAllString(s, "-")
	return strings.Trim(s, "-")
}

// --- Queries ---

func GetAllPosts(db *sql.DB) ([]Post, error) {
	rows, err := db.Query(`SELECT id, title, slug, content, published, created_at, updated_at FROM posts ORDER BY created_at DESC`)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	return scanPosts(rows)
}

func GetPublishedPosts(db *sql.DB) ([]Post, error) {
	rows, err := db.Query(`SELECT id, title, slug, content, published, created_at, updated_at FROM posts WHERE published=1 ORDER BY created_at DESC`)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	return scanPosts(rows)
}

func GetPostBySlug(db *sql.DB, slug string) (*Post, error) {
	row := db.QueryRow(`SELECT id, title, slug, content, published, created_at, updated_at FROM posts WHERE slug=?`, slug)
	return scanPost(row)
}

func GetPostByID(db *sql.DB, id int) (*Post, error) {
	row := db.QueryRow(`SELECT id, title, slug, content, published, created_at, updated_at FROM posts WHERE id=?`, id)
	return scanPost(row)
}

func CreatePost(db *sql.DB, title, content string, published bool) (*Post, error) {
	slug := Slugify(title)
	pub := 0
	if published {
		pub = 1
	}
	res, err := db.Exec(`INSERT INTO posts (title, slug, content, published) VALUES (?, ?, ?, ?)`, title, slug, content, pub)
	if err != nil {
		return nil, fmt.Errorf("insert post: %w", err)
	}
	id, _ := res.LastInsertId()
	return GetPostByID(db, int(id))
}

func UpdatePost(db *sql.DB, id int, title, content string, published bool) error {
	slug := Slugify(title)
	pub := 0
	if published {
		pub = 1
	}
	_, err := db.Exec(`UPDATE posts SET title=?, slug=?, content=?, published=?, updated_at=CURRENT_TIMESTAMP WHERE id=?`,
		title, slug, content, pub, id)
	return err
}

func DeletePost(db *sql.DB, id int) error {
	_, err := db.Exec(`DELETE FROM posts WHERE id=?`, id)
	return err
}

func scanPosts(rows *sql.Rows) ([]Post, error) {
	var posts []Post
	for rows.Next() {
		var p Post
		var pub int
		if err := rows.Scan(&p.ID, &p.Title, &p.Slug, &p.Content, &pub, &p.CreatedAt, &p.UpdatedAt); err != nil {
			return nil, err
		}
		p.Published = pub == 1
		posts = append(posts, p)
	}
	return posts, nil
}

func scanPost(row *sql.Row) (*Post, error) {
	var p Post
	var pub int
	err := row.Scan(&p.ID, &p.Title, &p.Slug, &p.Content, &pub, &p.CreatedAt, &p.UpdatedAt)
	if err != nil {
		return nil, err
	}
	p.Published = pub == 1
	return &p, nil
}
