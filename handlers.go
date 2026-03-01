package handlers

import (
	"html/template"
	"net/http"
	"path/filepath"
	"strconv"
	"strings"

	"cms/internal/database"
	"cms/internal/models"
)

type Handler struct {
	db *database.DB
}

func New(db *database.DB) *Handler {
	return &Handler{db: db}
}

func (h *Handler) render(w http.ResponseWriter, tmpl string, data any) {
	t, err := template.ParseFiles(
		filepath.Join("templates", "base.html"),
		filepath.Join("templates", tmpl),
	)
	if err != nil {
		http.Error(w, "Template error: "+err.Error(), 500)
		return
	}
	if err := t.ExecuteTemplate(w, "base", data); err != nil {
		http.Error(w, "Render error: "+err.Error(), 500)
	}
}

// GET / — public post list
func (h *Handler) ListPosts(w http.ResponseWriter, r *http.Request) {
	if r.URL.Path != "/" {
		http.NotFound(w, r)
		return
	}
	posts, err := models.GetPublishedPosts(h.db.DB)
	if err != nil {
		http.Error(w, err.Error(), 500)
		return
	}
	h.render(w, "index.html", map[string]any{"Posts": posts})
}

// GET /post/{slug}
func (h *Handler) ViewPost(w http.ResponseWriter, r *http.Request) {
	slug := strings.TrimPrefix(r.URL.Path, "/post/")
	post, err := models.GetPostBySlug(h.db.DB, slug)
	if err != nil || !post.Published {
		http.NotFound(w, r)
		return
	}
	h.render(w, "post.html", map[string]any{"Post": post})
}

// GET /admin
func (h *Handler) AdminDashboard(w http.ResponseWriter, r *http.Request) {
	posts, err := models.GetAllPosts(h.db.DB)
	if err != nil {
		http.Error(w, err.Error(), 500)
		return
	}
	h.render(w, "admin.html", map[string]any{"Posts": posts})
}

// GET/POST /admin/new
func (h *Handler) NewPost(w http.ResponseWriter, r *http.Request) {
	if r.Method == http.MethodPost {
		r.ParseForm()
		title := r.FormValue("title")
		content := r.FormValue("content")
		published := r.FormValue("published") == "on"

		if _, err := models.CreatePost(h.db.DB, title, content, published); err != nil {
			http.Error(w, err.Error(), 500)
			return
		}
		http.Redirect(w, r, "/admin", http.StatusSeeOther)
		return
	}
	h.render(w, "form.html", map[string]any{"Title": "New Post", "Post": nil})
}

// GET/POST /admin/edit/{id}
func (h *Handler) EditPost(w http.ResponseWriter, r *http.Request) {
	idStr := strings.TrimPrefix(r.URL.Path, "/admin/edit/")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		http.NotFound(w, r)
		return
	}

	if r.Method == http.MethodPost {
		r.ParseForm()
		title := r.FormValue("title")
		content := r.FormValue("content")
		published := r.FormValue("published") == "on"

		if err := models.UpdatePost(h.db.DB, id, title, content, published); err != nil {
			http.Error(w, err.Error(), 500)
			return
		}
		http.Redirect(w, r, "/admin", http.StatusSeeOther)
		return
	}

	post, err := models.GetPostByID(h.db.DB, id)
	if err != nil {
		http.NotFound(w, r)
		return
	}
	h.render(w, "form.html", map[string]any{"Title": "Edit Post", "Post": post})
}

// POST /admin/delete/{id}
func (h *Handler) DeletePost(w http.ResponseWriter, r *http.Request) {
	idStr := strings.TrimPrefix(r.URL.Path, "/admin/delete/")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		http.NotFound(w, r)
		return
	}
	models.DeletePost(h.db.DB, id)
	http.Redirect(w, r, "/admin", http.StatusSeeOther)
}
