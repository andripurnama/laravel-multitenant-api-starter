# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_ACCESS_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

You can retrieve your access token by logging in via the <code>POST /api/auth/login</code> endpoint. The token should be included in the <code>Authorization</code> header as a Bearer token: <code>Authorization: Bearer {token}</code>.
