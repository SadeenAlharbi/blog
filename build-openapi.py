#!/usr/bin/env python3
"""Generate public/openapi.json describing the project's real REST API v1.

Kept as a build helper so the spec is written once, validated, and dropped into
public/ — the Swagger UI page fetches it at runtime. Every path below mirrors a
route that actually exists in routes/api.php.
"""
import json
import pathlib

BEARER = "sanctumAuth"

# Sanctum's bearer token is the ONLY credential this API asks for. `public`
# is an empty list, which is how OpenAPI says "no authentication required" —
# Swagger UI then leaves those endpoints callable without pressing Authorize.
both = [{BEARER: []}]
public = []


def resp(code, desc, schema=None):
    r = {"description": desc}
    if schema:
        r["content"] = {"application/json": {"schema": schema}}
    return {str(code): r}


def ref(name):
    return {"$ref": f"#/components/schemas/{name}"}


def envelope(data_schema, message="OK"):
    return {
        "type": "object",
        "properties": {
            "data": data_schema,
            "message": {"type": "string", "example": message},
        },
    }


paginated = {
    "type": "object",
    "properties": {
        "data": {"type": "array", "items": ref("Post")},
        "links": {"type": "object"},
        "meta": {"type": "object"},
    },
}

validation_error = {
    "type": "object",
    "properties": {
        "message": {"type": "string"},
        "errors": {"type": "object", "additionalProperties": {"type": "array", "items": {"type": "string"}}},
    },
}

spec = {
    "openapi": "3.0.3",
    "info": {
        "title": "Saudi Knowledge Platform API",
        "version": "1.0.0",
        "description": (
            "REST API for منصة المعرفة السعودية.\n\n"
            "**Authentication — Laravel Sanctum bearer tokens.**\n\n"
            "`POST /auth/register` and `POST /auth/login` return a personal access token. "
            "Send it on every protected call as:\n\n"
            "```\nAuthorization: Bearer <token>\n```\n\n"
            "In Swagger UI press **Authorize**, paste the token, and the header is attached "
            "to each request you try. `POST /auth/logout` deletes the token server-side, so the "
            "same value stops working immediately.\n\n"
            "Endpoints marked without a padlock are public and need no token.\n\n"
            "**Authorization** (who may edit what) is enforced by Policies, so the API and the web "
            "app can never disagree. Publishing an article or writing a comment additionally needs a "
            "verified email address (`403` otherwise). A disabled account receives `403` and its "
            "tokens are revoked."
        ),
    },
    "servers": [{"url": "/api/v1", "description": "API v1"}],
    "tags": [
        {"name": "Auth", "description": "Registration, login, logout, current user"},
        {"name": "Posts", "description": "Articles"},
        {"name": "Comments", "description": "Comments on articles"},
        {"name": "Tags", "description": "Categories (a single central system)"},
        {"name": "Notifications", "description": "The signed-in user's notifications"},
    ],
    "components": {
        "securitySchemes": {
            BEARER: {
                "type": "http",
                "scheme": "bearer",
                "bearerFormat": "Sanctum",
                "description": "A Sanctum personal access token returned by login or register.",
            },
        },
        "schemas": {
            "User": {
                "type": "object",
                "properties": {
                    "id": {"type": "integer", "example": 1},
                    "name": {"type": "string", "example": "سدين"},
                    "email": {"type": "string", "format": "email"},
                    "role": {"type": "string", "enum": ["admin", "user"]},
                    "is_admin": {"type": "boolean"},
                    "created_at": {"type": "string", "format": "date-time"},
                },
            },
            "Tag": {
                "type": "object",
                "properties": {
                    "id": {"type": "integer"},
                    "name": {"type": "string", "example": "الاقتصاد"},
                    "slug": {"type": "string", "example": "economy"},
                    "posts_count": {"type": "integer"},
                },
            },
            "Comment": {
                "type": "object",
                "properties": {
                    "id": {"type": "integer"},
                    "content": {"type": "string"},
                    "status": {"type": "string", "enum": ["approved", "hidden"]},
                    "author": ref("User"),
                    "post_id": {"type": "integer"},
                    "created_at": {"type": "string", "format": "date-time"},
                },
            },
            "Post": {
                "type": "object",
                "properties": {
                    "id": {"type": "integer"},
                    "title": {"type": "string"},
                    "slug": {"type": "string"},
                    "content": {"type": "string"},
                    "image_url": {"type": "string", "nullable": True},
                    "status": {"type": "string", "enum": ["draft", "published", "scheduled"]},
                    "status_label": {"type": "string"},
                    "is_published": {"type": "boolean"},
                    "published_at": {"type": "string", "format": "date-time", "nullable": True},
                    "author": ref("User"),
                    "tags": {"type": "array", "items": ref("Tag")},
                    "comments_count": {"type": "integer"},
                    "views_count": {"type": "integer"},
                    "created_at": {"type": "string", "format": "date-time"},
                    "updated_at": {"type": "string", "format": "date-time"},
                },
            },
            "Notification": {
                "type": "object",
                "properties": {
                    "id": {"type": "string", "format": "uuid"},
                    "type": {"type": "string", "example": "NewCommentNotification"},
                    "data": {"type": "object"},
                    "read_at": {"type": "string", "format": "date-time", "nullable": True},
                    "is_read": {"type": "boolean"},
                    "created_at": {"type": "string", "format": "date-time"},
                },
            },
        },
        "responses": {},
    },
    "security": public,
    "paths": {},
}

P = spec["paths"]

# ------------------------------------------------------------------ Auth
P["/auth/register"] = {
    "post": {
        "tags": ["Auth"],
        "summary": "Create an account and receive a token",
        "security": public,
        "requestBody": {
            "required": True,
            "content": {"application/json": {"schema": {
                "type": "object",
                "required": ["name", "email", "password", "password_confirmation"],
                "properties": {
                    "name": {"type": "string", "maxLength": 255},
                    "email": {"type": "string", "format": "email"},
                    "password": {"type": "string", "minLength": 8, "format": "password"},
                    "password_confirmation": {"type": "string", "format": "password"},
                },
            }}},
        },
        "responses": {
            **resp(201, "Registered", envelope({"type": "object", "properties": {
                "user": ref("User"), "token": {"type": "string"}}}, "Registered successfully.")),
            **resp(401, "Unauthenticated — missing or invalid bearer token"),
            **resp(422, "Validation failed", validation_error),
            **resp(429, "Too many attempts"),
        },
    }
}

P["/auth/login"] = {
    "post": {
        "tags": ["Auth"],
        "summary": "Exchange credentials for a token",
        "description": "A disabled account receives 403 and any tokens it holds are revoked.",
        "security": public,
        "requestBody": {
            "required": True,
            "content": {"application/json": {"schema": {
                "type": "object",
                "required": ["email", "password"],
                "properties": {
                    "email": {"type": "string", "format": "email"},
                    "password": {"type": "string", "format": "password"},
                },
            }}},
        },
        "responses": {
            **resp(200, "Logged in", envelope({"type": "object", "properties": {
                "user": ref("User"), "token": {"type": "string"}}}, "Logged in successfully.")),
            **resp(401, "Unauthenticated — missing or invalid bearer token"),
            **resp(403, "Account disabled by the platform administrators"),
            **resp(422, "Invalid credentials", validation_error),
            **resp(429, "Too many attempts"),
        },
    }
}

P["/auth/logout"] = {
    "post": {
        "tags": ["Auth"],
        "summary": "Revoke the current token",
        "security": both,
        "responses": {**resp(200, "Logged out"), **resp(401, "Unauthenticated"), **resp(403, "Account disabled, not the owner, or email not verified")},
    }
}

P["/auth/me"] = {
    "get": {
        "tags": ["Auth"],
        "summary": "The authenticated user",
        "security": both,
        "responses": {
            **resp(200, "Current user", envelope(ref("User"))),
            **resp(401, "Unauthenticated"),
            **resp(403, "Account disabled, not the owner, or email not verified"),
        },
    }
}

# ------------------------------------------------------------------ Posts
P["/posts"] = {
    "get": {
        "tags": ["Posts"],
        "summary": "List articles",
        "description": (
            "Published articles only, unless an **administrator** narrows the listing "
            "with `status`. The parameter is ignored for guests and non-admins, and an "
            "unrecognised value falls back to published — the listing can never be "
            "widened by asking. This mirrors `GET /posts/{slug}`, which already lets an "
            "admin read an unpublished article.\n\n"
            "The admin may authenticate with either a bearer token or a session."
        ),
        "security": public,
        "parameters": [
            {"name": "search", "in": "query", "schema": {"type": "string"},
             "description": "Matches title, content or category name"},
            {"name": "tag", "in": "query", "schema": {"type": "string"}, "description": "Category slug or name"},
            {"name": "status", "in": "query",
             "schema": {"type": "string", "enum": ["published", "draft", "scheduled", "all"]},
             "description": "Admins only. Omit for the public default (published). "
                            "`all` returns every status."},
            {"name": "sort", "in": "query", "schema": {"type": "string", "enum": ["latest", "oldest", "title", "views"]},
             "description": "Ordering. Unpublished listings order by created_at, since a draft has no published_at."},
            {"name": "per_page", "in": "query", "schema": {"type": "integer", "default": 10}},
            {"name": "page", "in": "query", "schema": {"type": "integer", "default": 1}},
        ],
        "responses": {**resp(200, "Paginated articles", paginated), **resp(401, "Unauthenticated — missing or invalid bearer token")},
    },
    "post": {
        "tags": ["Posts"],
        "summary": "Create an article",
        "security": both,
        "requestBody": {
            "required": True,
            "content": {"multipart/form-data": {"schema": {
                "type": "object",
                "required": ["title", "content"],
                "properties": {
                    "title": {"type": "string", "maxLength": 255},
                    "slug": {"type": "string", "nullable": True},
                    "content": {"type": "string"},
                    "image": {"type": "string", "format": "binary", "description": "jpeg/png/webp, max 5 MB"},
                    "status": {"type": "string", "enum": ["draft", "published", "scheduled"]},
                    "published_at": {"type": "string", "format": "date-time", "nullable": True},
                    "tags": {"type": "array", "items": {"type": "string"},
                             "description": "Category slugs from the central list"},
                },
            }}},
        },
        "responses": {
            **resp(201, "Created", envelope(ref("Post"), "Post created successfully.")),
            **resp(401, "Unauthenticated"), **resp(403, "Account disabled, not the owner, or email not verified"),
            **resp(422, "Validation failed", validation_error), **resp(429, "Rate limited"),
        },
    },
}

P["/posts/{slug}"] = {
    "parameters": [{"name": "slug", "in": "path", "required": True, "schema": {"type": "string"}}],
    "get": {
        "tags": ["Posts"],
        "summary": "Fetch one article",
        "description": "Drafts and scheduled articles are visible only to their author or an administrator.",
        "security": public,
        "responses": {
            **resp(200, "The article", envelope(ref("Post"))),
            **resp(404, "Not found"),
            **resp(410, "Removed by the moderators"),
        },
    },
    "put": {
        "tags": ["Posts"], "summary": "Update an article (owner or admin)", "security": both,
        "responses": {**resp(200, "Updated", envelope(ref("Post"), "Post updated successfully.")),
                      **resp(403, "Not allowed"), **resp(404, "Not found"),
                      **resp(422, "Validation failed", validation_error)},
    },
    "patch": {
        "tags": ["Posts"], "summary": "Partially update an article", "security": both,
        "responses": {**resp(200, "Updated", envelope(ref("Post"))), **resp(403, "Not allowed")},
    },
    "delete": {
        "tags": ["Posts"], "summary": "Delete an article (owner or admin)", "security": both,
        "responses": {**resp(200, "Deleted"), **resp(403, "Not allowed"), **resp(404, "Not found")},
    },
}

# --------------------------------------------------------------- Comments
P["/posts/{slug}/comments"] = {
    "parameters": [{"name": "slug", "in": "path", "required": True, "schema": {"type": "string"}}],
    "get": {
        "tags": ["Comments"], "summary": "List approved comments", "security": public,
        "parameters": [{"name": "page", "in": "query", "schema": {"type": "integer"}}],
        "responses": {**resp(200, "Paginated comments", {
            "type": "object",
            "properties": {"data": {"type": "array", "items": ref("Comment")},
                           "links": {"type": "object"}, "meta": {"type": "object"}},
        })},
    },
    "post": {
        "tags": ["Comments"], "summary": "Add a comment", "security": both,
        "requestBody": {"required": True, "content": {"application/json": {"schema": {
            "type": "object", "required": ["content"],
            "properties": {"content": {"type": "string", "maxLength": 2000}},
        }}}},
        "responses": {**resp(201, "Created", envelope(ref("Comment"), "Comment added successfully.")),
                      **resp(401, "Unauthenticated"),
                      **resp(403, "Account disabled, or email not verified"),
                      **resp(422, "Validation failed", validation_error),
                      **resp(429, "Rate limited")},
    },
}

P["/comments/{comment}"] = {
    "parameters": [{"name": "comment", "in": "path", "required": True, "schema": {"type": "integer"}}],
    "delete": {
        "tags": ["Comments"],
        "summary": "Delete a comment (its author or an admin)",
        "security": both,
        "responses": {**resp(200, "Deleted"), **resp(401, "Unauthenticated"),
                      **resp(403, "Not allowed"), **resp(404, "Not found")},
    },
}

# ------------------------------------------------------------------- Tags
P["/tags"] = {
    "get": {
        "tags": ["Tags"], "summary": "List categories with article counts", "security": public,
        "responses": {**resp(200, "Categories", envelope({"type": "array", "items": ref("Tag")}))},
    },
    "post": {
        "tags": ["Tags"],
        "summary": "Materialise a category",
        "description": ("Categories are managed by administrators in the admin area. This endpoint "
                        "only creates a row for a category that already exists in the central list — "
                        "it never invents one."),
        "security": both,
        "requestBody": {"required": True, "content": {"application/json": {"schema": {
            "type": "object", "required": ["name"],
            "properties": {"name": {"type": "string", "description": "A canonical category slug or its Arabic name"}},
        }}}},
        "responses": {**resp(201, "Created", envelope(ref("Tag"))), **resp(200, "Already existed", envelope(ref("Tag"))),
                      **resp(422, "Not a known category", validation_error)},
    },
}

# ---------------------------------------------------------- Notifications
P["/notifications"] = {
    "get": {
        "tags": ["Notifications"], "summary": "List the user's notifications", "security": both,
        "parameters": [
            {"name": "unread", "in": "query", "schema": {"type": "boolean"}, "description": "Only unread"},
            {"name": "per_page", "in": "query", "schema": {"type": "integer", "default": 15}},
            {"name": "page", "in": "query", "schema": {"type": "integer"}},
        ],
        "responses": {**resp(200, "Paginated notifications", {
            "type": "object",
            "properties": {
                "data": {"type": "array", "items": ref("Notification")},
                "links": {"type": "object"},
                "meta": {"type": "object", "properties": {"unread_count": {"type": "integer"}}},
            },
        }), **resp(401, "Unauthenticated")},
    }
}

P["/notifications/unread-count"] = {
    "get": {
        "tags": ["Notifications"], "summary": "Unread count", "security": both,
        "responses": {**resp(200, "Count", envelope({"type": "object", "properties": {"unread_count": {"type": "integer"}}}))},
    }
}

P["/notifications/{notification}/read"] = {
    "parameters": [{"name": "notification", "in": "path", "required": True,
                    "schema": {"type": "string", "format": "uuid"}}],
    "patch": {
        "tags": ["Notifications"], "summary": "Mark one notification as read", "security": both,
        "responses": {**resp(200, "Marked as read", envelope(ref("Notification"))),
                      **resp(404, "Not found (or belongs to another user)")},
    },
    "post": {
        "tags": ["Notifications"], "summary": "Mark one notification as read (alias)", "security": both,
        "responses": {**resp(200, "Marked as read", envelope(ref("Notification")))},
    },
}

P["/notifications/read-all"] = {
    "patch": {
        "tags": ["Notifications"], "summary": "Mark every notification as read", "security": both,
        "responses": {**resp(200, "All marked as read")},
    },
    "post": {
        "tags": ["Notifications"], "summary": "Mark every notification as read (alias)", "security": both,
        "responses": {**resp(200, "All marked as read")},
    },
}

out = pathlib.Path(__file__).parent / "public" / "openapi.json"
out.parent.mkdir(parents=True, exist_ok=True)
out.write_text(json.dumps(spec, ensure_ascii=False, indent=2), encoding="utf-8")
print(f"wrote {out} — {len(spec['paths'])} paths")
