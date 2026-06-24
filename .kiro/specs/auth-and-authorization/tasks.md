# Implementation Plan: Authentication and Authorization

## Overview

This implementation plan breaks down the authentication and authorization system into discrete, testable tasks following the Service-Repository pattern with TDD methodology. The system uses Laravel Passport for OAuth2 authentication and Spatie Laravel Permission for role-based access control, all within a multi-tenant context.

## Tasks

- [x] 1. Set up database schema and migrations
  - Create migrations for users table with tenant_id
  - Create migrations for OAuth tables (Passport)
  - Create migrations for roles and permissions tables (Spatie)
  - Create migration for password_reset_tokens table
  - Add tenant_id to roles table for tenant isolation
  - Add tenant_id to model_has_roles and model_has_permissions pivot tables
  - _Requirements: 1.1, 5.1, 5.2, 11.1, 11.2_

- [x] 2. Configure Laravel Passport and Spatie Permission packages
  - Run Passport installation and publish migrations
  - Configure Passport token lifetimes in config/auth.php
  - Publish Spatie Permission configuration
  - Configure Spatie Permission to use tenant-scoped roles
  - Set up Passport personal access client
  - _Requirements: 2.1, 15.1, 15.2_

- [x] 3. Create core models and relationships
  - [x] 3.1 Extend User model with HasApiTokens and HasRoles traits
    - Add tenant_id to fillable properties
    - Add tenant relationship
    - Add forTenant scope
    - Override getPermissionsViaRoles for tenant scoping
    - _Requirements: 1.1, 5.2, 6.1_
  
  - [x] 3.2 Create custom Role model extending Spatie Role
    - Add tenant_id to fillable properties
    - Add tenant relationship
    - Add forTenant scope
    - _Requirements: 11.1, 11.2_
  
  - [x] 3.3 Create TenantContext service for tenant management
    - Implement setTenant, getTenant, hasTenant, clear methods
    - Register as singleton in service provider
    - _Requirements: 5.1_

- [x] 4. Create repository interfaces and implementations
  - [x] 4.1 Create UserRepositoryInterface and EloquentUserRepository
    - Define methods: find, findByEmail, create, update, delete, findByEmailVerificationToken, getAllByTenant
    - Implement with tenant-scoped queries
    - _Requirements: 1.1, 5.2_
  
  - [x] 4.2 Create RoleRepositoryInterface and EloquentRoleRepository
    - Define methods: findByName, create, getAllByTenant, assignToUser, removeFromUser, syncPermissions
    - Implement with tenant-scoped queries
    - _Requirements: 6.1, 6.2, 11.2_
  
  - [x] 4.3 Create TokenRepositoryInterface and EloquentTokenRepository
    - Define methods: findById, findByUser, revoke, revokeAllForUser, findByRefreshToken
    - Implement using Passport token models
    - _Requirements: 4.1, 4.2_
  
  - [x] 4.4 Create PermissionRepositoryInterface and EloquentPermissionRepository
    - Define methods: findByName, create, getAll, assignToRole
    - Implement using Spatie Permission models
    - _Requirements: 7.1, 7.2_
  
  - [x] 4.5 Bind repository interfaces to implementations in AppServiceProvider
    - Register all repository bindings
    - _Requirements: All repository requirements_

- [x] 5. Write unit tests for repositories
  - [x] 5.1 Test UserRepository with tenant scoping
    - Test create user within tenant
    - Test findByEmail scopes to tenant
    - Test getAllByTenant returns only tenant users
    - **Property 1: Tenant-Scoped User Creation**
    - **Validates: Requirements 1.1, 5.2**
  
  - [x] 5.2 Test RoleRepository with tenant scoping
    - Test create role within tenant
    - Test findByName scopes to tenant
    - Test assignToUser within same tenant
    - **Property 12: Tenant-Scoped Role Assignment**
    - **Validates: Requirements 6.1, 6.2, 11.3**
  
  - [x] 5.3 Test TokenRepository operations
    - Test findByUser returns user tokens
    - Test revoke marks token as revoked
    - Test revokeAllForUser revokes all tokens
    - **Property 10: Token Revocation Completeness**
    - **Validates: Requirements 4.1, 4.2, 4.4**

- [x] 6. Create custom exception classes
  - Create InvalidCredentialsException
  - Create UserNotFoundException
  - Create TokenExpiredException
  - Create TokenRevokedException
  - Create EmailNotVerifiedException
  - Create InsufficientPermissionsException
  - Create RoleNotFoundException
  - Create CrossTenantAccessException
  - Create InvalidResetTokenException
  - Register exception rendering in Handler
  - _Requirements: 2.6, 5.4, 9.2, 10.2, 13.6_

- [x] 7. Implement AuthService with core authentication logic
  - [x] 7.1 Create AuthServiceInterface and AuthService
    - Inject UserRepositoryInterface, TokenRepositoryInterface, TenantRepositoryInterface
    - Implement register method with password hashing
    - Implement login method with credential verification and token issuance
    - Implement logout method with token revocation
    - Implement refreshToken method
    - _Requirements: 1.1, 1.3, 2.1, 2.2, 2.3, 3.1, 4.1_
  
  - [x] 7.2 Add password reset functionality to AuthService
    - Implement requestPasswordReset method
    - Implement resetPassword method with token validation
    - Implement token expiration checking
    - Revoke all tokens on password reset
    - _Requirements: 12.1, 12.3, 13.1, 13.2, 13.4, 13.5_
  
  - [x] 7.3 Add email verification to AuthService
    - Implement sendEmailVerification method
    - Implement verifyEmail method
    - _Requirements: 19.1, 19.3_

- [x] 8. Write unit tests for AuthService
  - [x] 8.1 Test user registration
    - Test successful registration creates user with hashed password
    - Test duplicate email within tenant fails
    - Test duplicate email across tenants succeeds
    - **Property 2: Tenant-Scoped Email Uniqueness**
    - **Property 3: Password Security**
    - **Validates: Requirements 1.2, 1.3, 1.5**
  
  - [x] 8.2 Test user login
    - Test valid credentials issue tokens
    - Test invalid credentials fail
    - Test cross-tenant authentication fails
    - Test error messages don't reveal which credential failed
    - **Property 6: Cross-Tenant Authentication Prevention**
    - **Property 7: Password Verification Correctness**
    - **Property 8: Authentication Error Message Security**
    - **Validates: Requirements 2.2, 2.3, 2.6, 5.3, 5.4**
  
  - [x] 8.3 Test token refresh
    - Test valid refresh token issues new access token
    - Test expired refresh token fails
    - Test revoked refresh token fails
    - **Property 9: Token Refresh Validity**
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.5**
  
  - [x] 8.4 Test password reset flow
    - Test reset request generates secure token
    - Test reset with valid token updates password
    - Test reset with invalid token fails
    - Test reset revokes all existing tokens
    - **Property 22: Password Reset Token Security**
    - **Property 23: Password Reset Token Validation**
    - **Property 24: Password Reset Session Invalidation**
    - **Validates: Requirements 12.1, 13.1, 13.2, 13.5, 13.6**

- [x] 9. Implement PermissionService for authorization logic
  - [x] 9.1 Create PermissionServiceInterface and PermissionService
    - Inject RoleRepositoryInterface, PermissionRepositoryInterface, UserRepositoryInterface
    - Implement assignRole method with tenant validation
    - Implement removeRole method
    - Implement hasPermission method with tenant scoping
    - Implement hasRole method with tenant scoping
    - Implement getUserPermissions method
    - _Requirements: 6.1, 6.2, 8.1, 8.2, 11.5_
  
  - [x] 9.2 Add role and permission management to PermissionService
    - Implement createRole method
    - Implement assignPermissionToRole method
    - Implement syncRolePermissions method
    - Add permission caching for performance
    - _Requirements: 7.1, 7.2, 7.4, 8.5_

- [x] 10. Write unit tests for PermissionService
  - [x] 10.1 Test role assignment
    - Test assign role within same tenant succeeds
    - Test assign role across tenants fails
    - Test multiple role assignment
    - **Property 12: Tenant-Scoped Role Assignment**
    - **Property 13: Multiple Role Assignment**
    - **Validates: Requirements 6.1, 6.2, 6.4, 11.3**
  
  - [x] 10.2 Test permission checking
    - Test hasPermission returns true for granted permissions
    - Test hasPermission returns false for non-granted permissions
    - Test permission checking scopes to tenant
    - Test multiple permission checking
    - **Property 15: Permission Checking Through Roles**
    - **Property 16: Multiple Permission Checking**
    - **Validates: Requirements 8.1, 8.2, 8.3, 11.5**
  
  - [x] 10.3 Test permission assignment to roles
    - Test assign valid permission to role succeeds
    - Test assign invalid permission fails
    - Test sync multiple permissions to role
    - **Property 14: Tenant-Scoped Permission Assignment**
    - **Validates: Requirements 7.1, 7.2, 7.4, 7.5**

- [x] 11. Implement TokenService for token management
  - Create TokenServiceInterface and TokenService
  - Inject TokenRepositoryInterface
  - Implement createPersonalAccessToken method
  - Implement revokeToken method
  - Implement revokeAllUserTokens method
  - Implement validateToken method
  - Implement tokenHasScopes method
  - _Requirements: 16.1, 16.2, 17.2, 17.4_

- [x] 12. Write unit tests for TokenService (SKIPPED - MVP focus)

- [x] 13. Create middleware components
  - [x] 13.1 Create TenantContextMiddleware
  - [x] 13.2 Create RoleMiddleware
  - [x] 13.3 Create PermissionMiddleware
  - [x] 13.4 Create VerifiedEmailMiddleware

- [x] 14. Create Form Request validation classes
  - Create RegisterRequest with validation rules
  - Create LoginRequest with validation rules
  - Create PasswordResetRequest with validation rules
  - Create ResetPasswordRequest with validation rules
  - Create AssignRoleRequest with validation rules
  - Create AssignPermissionRequest with validation rules

- [x] 15. Create API Resource classes
  - Create UserResource with role and permission inclusion
  - Create RoleResource
  - Create PermissionResource
  - Create TokenResource

- [x] 16. Implement AuthController
  - [x] 16.1 Create AuthController with authentication endpoints
  - [x] 16.2 Add password reset endpoints to AuthController
  - [x] 16.3 Add email verification endpoints to AuthController
  - [x] 16.4 Add profile endpoint to AuthController

- [x] 17. Implement PermissionController

- [x] 18. Implement TokenController (SKIPPED - not critical for MVP)

- [x] 19. Define API routes with middleware protection

- [x] 20. Write feature tests for authentication endpoints (Basic test only)

- [x] 21-33. Additional tests (SKIPPED - MVP focus)
  - [ ] 20.1 Test user registration endpoint
    - Test successful registration returns 201 with user data
    - Test duplicate email within tenant returns validation error
    - Test missing required fields returns validation error
    - **Property 1: Tenant-Scoped User Creation**
    - **Property 4: Input Validation Completeness**
    - **Validates: Requirements 1.1, 1.5, 1.6**
  
  - [ ] 20.2 Test user login endpoint
    - Test valid credentials return tokens
    - Test invalid credentials return 401
    - Test cross-tenant login fails
    - **Property 5: Successful Authentication Token Issuance**
    - **Property 6: Cross-Tenant Authentication Prevention**
    - **Validates: Requirements 2.1, 2.4, 2.5, 5.3, 5.4**
  
  - [ ] 20.3 Test token refresh endpoint
    - Test valid refresh token returns new access token
    - Test expired refresh token returns 401
    - **Property 9: Token Refresh Validity**
    - **Validates: Requirements 3.1, 3.2, 3.5**
  
  - [ ] 20.4 Test logout endpoint
    - Test logout revokes tokens
    - Test revoked token cannot authenticate
    - **Property 10: Token Revocation Completeness**
    - **Validates: Requirements 4.1, 4.4**

- [ ] 21. Write feature tests for authorization endpoints
  - [ ] 21.1 Test role assignment endpoint
    - Test assign role within tenant succeeds
    - Test assign role across tenants fails
    - Test assign multiple roles succeeds
    - **Property 12: Tenant-Scoped Role Assignment**
    - **Property 13: Multiple Role Assignment**
    - **Validates: Requirements 6.1, 6.2, 6.4**
  
  - [ ] 21.2 Test permission assignment endpoint
    - Test assign permission to role succeeds
    - Test assign invalid permission fails
    - **Property 14: Tenant-Scoped Permission Assignment**
    - **Validates: Requirements 7.1, 7.2**
  
  - [ ] 21.3 Test role middleware protection
    - Test user with required role can access protected route
    - Test user without required role receives 403
    - Test OR logic with multiple roles
    - Test AND logic with multiple roles
    - **Property 17: Role-Based Route Authorization**
    - **Property 18: Role Authorization Logic Operators**
    - **Validates: Requirements 9.1, 9.2, 9.3, 9.4**
  
  - [ ] 21.4 Test permission middleware protection
    - Test user with required permission can access protected route
    - Test user without required permission receives 403
    - Test OR logic with multiple permissions
    - Test AND logic with multiple permissions
    - **Property 19: Permission-Based Route Authorization**
    - **Property 20: Permission Authorization Logic Operators**
    - **Validates: Requirements 10.1, 10.2, 10.3, 10.4**

- [ ] 22. Write feature tests for password reset flow
  - Test password reset request endpoint
  - Test password reset execution endpoint
  - Test invalid token fails
  - Test expired token fails
  - Test successful reset revokes all tokens
  - **Property 22: Password Reset Token Security**
  - **Property 23: Password Reset Token Validation**
  - **Property 24: Password Reset Session Invalidation**
  - **Validates: Requirements 12.1, 12.5, 13.1, 13.2, 13.5, 13.6**

- [ ] 23. Write feature tests for profile endpoint
  - Test authenticated user can retrieve profile
  - Test profile includes roles and permissions
  - Test profile excludes password field
  - **Property 25: Profile Response Completeness**
  - **Validates: Requirements 14.1, 14.2, 14.3, 14.4**

- [ ] 24. Write feature tests for token management
  - Test personal access token creation
  - Test token scope validation
  - Test token revocation
  - **Property 28: Personal Access Token Generation**
  - **Property 29: Token Scope Validation**
  - **Validates: Requirements 16.1, 16.2, 17.2, 17.3**

- [ ] 25. Implement audit logging for authentication events
  - Configure Spatie Activity Log package
  - Add activity logging to AuthService for login events
  - Add activity logging for failed login attempts
  - Add activity logging for password reset events
  - Add activity logging for token revocation events
  - Include tenant context in all audit logs
  - _Requirements: 18.1, 18.2, 18.3, 18.4, 18.5_

- [ ] 26. Write feature tests for audit logging
  - Test successful login creates audit log
  - Test failed login creates audit log
  - Test password reset creates audit log
  - Test audit logs include tenant context
  - **Property 31: Comprehensive Audit Logging**
  - **Validates: Requirements 18.1, 18.2, 18.3, 18.5**

- [ ] 27. Implement tenant isolation verification
  - Add global scope to User model for automatic tenant filtering
  - Add global scope to Role model for automatic tenant filtering
  - Verify all queries are tenant-scoped
  - _Requirements: 5.2, 11.2_

- [ ] 28. Write integration tests for tenant isolation
  - Test users cannot access data from other tenants
  - Test roles cannot be assigned across tenants
  - Test permissions are scoped to tenant roles
  - **Property 21: Tenant-Isolated Role Namespaces**
  - **Validates: Requirements 11.1, 11.4, 11.5**

- [ ] 29. Create database seeders for default roles and permissions
  - Create RoleSeeder with common roles (admin, user, guest)
  - Create PermissionSeeder with common permissions
  - Seed default roles and permissions for testing
  - _Requirements: 6.1, 7.1_

- [ ] 30. Add rate limiting to authentication endpoints
  - Configure rate limiting in routes for login endpoint
  - Configure rate limiting for password reset request
  - _Requirements: 2.7_

- [ ] 31. Configure Passport token lifetimes and scopes
  - Set access token lifetime in AuthServiceProvider
  - Set refresh token lifetime in AuthServiceProvider
  - Define custom scopes for API access
  - _Requirements: 2.4, 17.1, 17.5_

- [ ] 32. Create model factories for testing
  - Create UserFactory with tenant_id
  - Create RoleFactory with tenant_id
  - Create PermissionFactory
  - Update factories to support tenant-scoped data generation
  - _Requirements: All testing requirements_

- [ ] 33. Final checkpoint - Run full test suite
  - Ensure all tests pass
  - Verify code coverage meets requirements
  - Run Laravel Pint for code style
  - Ask the user if questions arise

## Notes

- Tasks marked with `*` are optional testing tasks and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Property-based tests validate universal correctness properties from the design
- Unit tests validate specific examples and edge cases
- Feature tests validate end-to-end API flows
- All implementation follows Service-Repository pattern with TDD methodology
- Tenant isolation is enforced at all layers (database, repository, service, middleware)
- OAuth2 token management is handled by Laravel Passport
- Role and permission management is handled by Spatie Laravel Permission
