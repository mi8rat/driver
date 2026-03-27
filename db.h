#ifndef DB_H
#define DB_H

#include <sqlite3.h>

int init_db(sqlite3 **db);
void close_db(sqlite3 *db);

int create_post(sqlite3 *db, const char *title, const char *content);
int list_posts(sqlite3 *db);
int get_post(sqlite3 *db, int id);
int update_post(sqlite3 *db, int id, const char *title, const char *content);
int delete_post(sqlite3 *db, int id);

#endif
