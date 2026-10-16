# 0003 Guarded `role`, no remember token

## Context
Defect D6 reset the admin role on every login. The contract schema has no `remember_token` and no password.

## Decision
`User::$fillable` is `google_id`, `email`, `name`; `role` is not mass-assignable, so no `updateOrCreate` in the OAuth callback can write it (factories are unguarded and still set it). The `users` table has no `remember_token`: the app never uses "remember me", and the session guard works without it.

## Consequences
- Tests and commands that set a role use the factory `admin()` state or assign `$user->role` explicitly.
- Adding "remember me" later needs a migration.
