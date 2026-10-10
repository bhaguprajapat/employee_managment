# REST API reference

Base URL (Laragon): `http://employee-management.test/api`

All requests and responses are JSON. Send these headers on every call:

```
Accept: application/json
Content-Type: application/json        (for POST requests with a body)
Authorization: Bearer {token}         (every endpoint except POST /login)
```

## Response format

Every response has the same shape:

```json
{
  "success": true,
  "message": "Employees retrieved.",
  "data": {},
  "errors": null
}
```

On failure, `success` is `false`, `data` is `null`, and `errors` holds field
messages for validation failures (HTTP 422) or `null` otherwise.

## Status codes

| Code | Meaning |
|---|---|
| 200 | OK |
| 201 | Created (check-in recorded) |
| 401 | Missing, invalid, or revoked token; or wrong email/password at login |
| 403 | Logged in, but not allowed (e.g. employee listing all employees, or a deactivated account) |
| 404 | Resource not found |
| 409 | Conflict with existing data (duplicate check-in, duplicate check-out) |
| 422 | Validation failed, or action out of order (check-out without a check-in) |
| 429 | Too many requests (login is limited to 10 per minute per IP) |
| 500 | Server error (details are hidden unless `APP_DEBUG=true`) |

---

## POST /login

Exchange an email and password for an API token. No token needed.

**Request**

```json
{
  "email": "admin@example.com",
  "password": "your-password",
  "device_name": "postman"
}
```

`device_name` is optional and only labels the token.

**200 OK**

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "token": "1|abcdef...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "System Admin",
      "email": "admin@example.com",
      "role": "admin",
      "employee_id": null
    }
  },
  "errors": null
}
```

**Errors:** 422 (missing or invalid fields), 401 (wrong credentials),
403 (account deactivated).

---

## GET /employees

Admin only. Paginated list of employees.

**Query parameters (all optional)**

| Name | Example | Notes |
|---|---|---|
| `q` | `Ravi` | Searches name, email, code, designation |
| `status` | `active` | `active` or `inactive` |
| `department_id` | `2` | Must exist |
| `per_page` | `10` | 1 to 100, default 10 |

**200 OK**

```json
{
  "success": true,
  "message": "Employees retrieved.",
  "data": {
    "items": [
      {
        "id": 3,
        "employee_code": "EMP1001",
        "name": "Asha Verma",
        "email": "asha.verma@example.com",
        "mobile_number": "9876543210",
        "department": { "id": 1, "name": "Engineering" },
        "designation": "Software Engineer",
        "joining_date": "2024-04-01",
        "status": "active"
      }
    ],
    "meta": { "current_page": 1, "per_page": 10, "total": 1, "last_page": 1 }
  },
  "errors": null
}
```

**Errors:** 401 (no token), 403 (not an admin), 422 (bad query value).

---

## GET /employees/{id}

Admin: any employee. Employee: only their own record.

**200 OK** returns one employee object in `data`, in the same shape as above.

**Errors:** 401, 403 (another employee's record), 404 (no such employee).

---

## POST /attendance/check-in

Employee only (the login must be linked to an employee profile).
Records today's check-in.

**201 Created**

```json
{
  "success": true,
  "message": "Check-in recorded.",
  "data": {
    "id": 12,
    "employee_id": 3,
    "employee": { "id": 3, "name": "Asha Verma", "employee_code": "EMP1001" },
    "attendance_date": "2026-10-10",
    "check_in_at": "2026-10-10T09:02:14+05:30",
    "check_out_at": null,
    "working_minutes": null,
    "working_hours": null
  },
  "errors": null
}
```

**Errors:** 401 (no token), 403 (login not linked to an employee, or
employee inactive), 409 (already checked in today).

---

## POST /attendance/check-out

Employee only. Records today's check-out. Requires a check-in today and no
earlier check-out today.

**200 OK**: same shape as check-in, with `check_out_at`, `working_minutes`,
and `working_hours` filled in, e.g. `"working_hours": "8h 30m"`.

**Errors:** 401, 403 (not linked to an employee), 409 (already checked out
today), 422 (no check-in today).

---

## GET /attendance

Paginated attendance history. Employees see only their own rows. Admins see
all rows and can filter.

**Query parameters (all optional)**

| Name | Example | Notes |
|---|---|---|
| `employee_id` | `3` | Admin only; ignored for employees |
| `date` | `2026-10-10` | One specific day (`YYYY-MM-DD`) |
| `from` | `2026-10-01` | Start of a range (inclusive) |
| `to` | `2026-10-10` | End of a range (inclusive), must be on or after `from` |
| `per_page` | `15` | 1 to 100, default 15 |

**200 OK**

```json
{
  "success": true,
  "message": "Attendance retrieved.",
  "data": {
    "items": [ { "...same shape as check-in...": "" } ],
    "meta": { "current_page": 1, "per_page": 15, "total": 1, "last_page": 1 }
  },
  "errors": null
}
```

**Errors:** 401, 422 (bad date format, or `to` before `from`).

---

## Rules enforced by the API

- One attendance record per employee per day, enforced by the database as well.
- Check-out needs a check-in on the same day.
- A deactivated employee or account gets 403 on every call, and the token is
  revoked on that call.
- Wrong email and wrong password return the same message, so accounts cannot
  be guessed.
