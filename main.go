package main

import (
	"fmt"
	"log"
	"net/http"

	"cms/internal/database"
	"cms/internal/handlers"
)

func main() {
	// Initialize DB
	db, err := database.Init("cms.db")
	if err != nil {
		log.Fatal("Failed to initialize database:", err)
	}
	defer db.Close()

	// Setup routes
	mux := http.NewServeMux()

	h := handlers.New(db)

	// Static files
	mux.Handle("/static/", http.StripPrefix("/static/", http.FileServer(http.Dir("static"))))

	// Pages
	mux.HandleFunc("/", h.ListPosts)
	mux.HandleFunc("/post/", h.ViewPost)
	mux.HandleFunc("/admin", h.AdminDashboard)
	mux.HandleFunc("/admin/new", h.NewPost)
	mux.HandleFunc("/admin/edit/", h.EditPost)
	mux.HandleFunc("/admin/delete/", h.DeletePost)

	fmt.Println("🚀 CMS running at http://localhost:8080")
	log.Fatal(http.ListenAndServe(":8080", mux))
}
