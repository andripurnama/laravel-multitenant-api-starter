# Introduction

Multi-tenant API with header-based tenant isolation.

<aside>
    <strong>Base URL</strong>: <code>http://laravel-multi-tenant-api-boilerplate.test</code>
</aside>

    This documentation aims to provide all the information you need to work with our API.

    <aside>As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
    You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).</aside>

    ## Multi-Tenant Architecture

    **Important:** All API requests must include the `X-Tenant-ID` header to specify the tenant context.

    ```
    X-Tenant-ID: 1
    ```

    Without this header, requests will fail with a 400 error: `{"error": "Tenant ID required"}`

    ### Test Tenant
    A test tenant is available for development:
    - **Tenant ID**: 1
    - **Tenant Name**: Test Tenant
    - **Tenant Slug**: test-tenant

    Use `X-Tenant-ID: 1` in all your API requests during testing.

