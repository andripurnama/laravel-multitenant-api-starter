# Laravel Multi-Tenant API Boilerplate

A production-ready SaaS foundation built with Laravel 13, PostgreSQL, Sanctum, and Spatie packages.

This boilerplate provides a scalable starting point for multi-tenant applications by solving common infrastructure concerns such as authentication, tenant isolation, authorization, API documentation, testing, and maintainable application architecture.

Instead of rebuilding these concerns for every project, teams can focus directly on delivering business features.

---

## Why This Boilerplate Exists

Most Laravel starters focus on authentication and CRUD functionality but leave critical SaaS concerns to be implemented later.

This project addresses common production requirements:

* Tenant data isolation
* Role-based access control
* Secure API authentication
* Consistent error handling
* Testable application architecture
* Automated API documentation
* Scalable service boundaries

The goal is to provide a foundation suitable for ERP systems, internal business platforms, B2B SaaS products, and enterprise applications.

---

## Core Principles

### Multi-Tenant by Default

Every business entity belongs to a tenant.

Tenant isolation is enforced through:

* Tenant middleware
* Tenant-aware models
* Repository-level constraints
* Automated test coverage

### Service-Oriented Architecture

Business logic is separated from controllers and persistence layers.

```text
HTTP Request
    ↓
Middleware
    ↓
Controller
    ↓
Service Layer
    ↓
Repository Layer
    ↓
Database
```

This approach improves:

* Maintainability
* Testability
* Scalability
* Team collaboration

### API-First Development

The entire application is designed around API consumption.

Features include:

* Token authentication
* Structured JSON responses
* Standardized error handling
* Generated API documentation
* OpenAPI-ready architecture

### Enterprise-Oriented Security

Security is built into the foundation through:

* Sanctum token authentication
* Password reset flows
* Email verification
* Tenant isolation
* Role-based access control
* Permission-based authorization

---

## Feature Overview

### Authentication & User Management

* User registration
* Login and logout
* Password reset
* Email verification
* Profile management
* Token-based authentication

### Multi-Tenancy

* Shared database architecture
* Header-based tenant resolution
* Automatic tenant scoping
* Tenant-aware repositories

### Authorization

* Tenant-scoped roles
* Tenant-scoped permissions
* Role assignment
* Permission synchronization

### Developer Experience

* Pest 4 testing
* Scribe documentation
* Laravel Pint formatting
* Repository pattern
* Service layer architecture
* Structured exception handling

---

## Intended Use Cases

This boilerplate is suitable for:

* SaaS products
* ERP systems
* Inventory systems
* Procurement platforms
* Clinic management systems
* CRM platforms
* Internal business applications
* Multi-company software solutions

---

## Technology Stack

| Layer          | Technology                  |
| -------------- | --------------------------- |
| Framework      | Laravel 13                  |
| Language       | PHP 8.3+                    |
| Database       | PostgreSQL                  |
| Authentication | Laravel Sanctum             |
| Multi-Tenancy  | Spatie Laravel Multitenancy |
| Authorization  | Spatie Laravel Permission   |
| Testing        | Pest 4                      |
| Documentation  | Scribe                      |
| Code Quality   | Laravel Pint                |

## Architecture Decisions

### Why Shared Database Multi-Tenancy?

This project uses a shared database with tenant-level isolation.

Benefits:

* Lower infrastructure cost
* Easier maintenance
* Simpler deployment
* Faster onboarding of new tenants

Tradeoffs:

* Requires strict tenant scoping
* Greater responsibility in application design

This boilerplate addresses these concerns through middleware enforcement and tenant-aware models.

---

### Why Service & Repository Pattern?

Laravel applications often place business logic directly inside controllers or models.

This project separates responsibilities into:

* Controllers for HTTP concerns
* Services for business rules
* Repositories for data access

Benefits:

* Easier testing
* Better maintainability
* Cleaner separation of concerns
* Reduced coupling

---

### Why Sanctum Instead of JWT?

Sanctum was selected because it:

* Is maintained by Laravel
* Provides first-party support
* Supports personal access tokens
* Requires less complexity than JWT implementations

For most SaaS APIs, Sanctum provides all required authentication capabilities without introducing unnecessary overhead.
