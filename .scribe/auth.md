# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_JWT_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Dapatkan token dari endpoint <code>POST /api/auth/login</code>, lalu kirim sebagai Bearer token.
