# User Lifecycle

QDB is not an open-registration application. User accounts are created deliberately by the site owner or bootstrap super-admin.

## Intended Flow

The first administrator is bootstrapped during setup, usually with a direct SQL insert from `docs/database.md`. The first user is treated as the super-admin by convention: `userid=1`, `isadmin=1`, and `enabled=1`.

After setup, the super-admin signs in and creates invited users from the admin panel. Created users are either moderators or administrators based on the `isadmin` value selected by the super-admin.

New users receive a temporary password out-of-band from the super-admin. They should sign in once with that temporary password and immediately change it from the Change Password panel. Normal users can only change their own password.

There is no full Manage Users interface yet. Do not enable public registration.

## State Machine

```text
BOOTSTRAP_ADMIN_CREATED
  -> SUPERADMIN_LOGIN
  -> CREATE_INVITED_USER
  -> USER_FIRST_LOGIN
  -> USER_CHANGE_PASSWORD
  -> ACTIVE_USER
```

## Future States

These states are intended for a later Manage Users pass:

- `DISABLED_USER`
- `PASSWORD_RESET_REQUIRED`
- `DELETED_USER` or `RETIRED_USER`, if account retirement is ever implemented

## Current Notes

- `qdbusers.enabled=0` users are refused during password authentication and session reload.
- `userid=1` remains the super-admin convention and should not be demoted by future user-management tooling.
- Temporary passwords are not logged or emailed by the application.
