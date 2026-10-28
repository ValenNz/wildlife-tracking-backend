# API Testing Guide for Apidog

## A. Introduction to Apidog Setup for Laravel API

### Base URL
Set the base URL in Apidog to:
```
http://localhost:8000/api
```

### Required Headers
For all API requests, use the following headers:
```json
{
  "Accept": "application/json",
  "Content-Type": "application/json"
}
```

### Laravel Sanctum Authentication in Apidog

#### Option 1: Bearer Token Authentication
1. Send a `POST` request to `/login` with valid credentials.
2. Extract the token from the response.
3. Add the token to subsequent requests in the Authorization header:
```json
{
  "Authorization": "Bearer {token}",
  "Accept": "application/json"
}
```

#### Option 2: Cookie-Based Authentication (SPA)
For frontend SPAs using cookie-based Sanctum auth:
1. First, send a `GET` request to `http://localhost:8000/sanctum/csrf-cookie` to obtain the CSRF cookie.
2. Ensure Apidog's cookie manager is enabled and stores cookies from the domain.
3. Send the login request with `withCredentials` equivalent enabled.
4. Subsequent requests will automatically include session cookies.

## B. Standardized Response Format Reference

### Success Response
```json
{
  "success": true,
  "message": "Success message",
  "data": {}
}
```

### Error Response - Validation (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": [
      "Error message"
    ]
  }
}
```

### Error Response - Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

### Error Response - Not Found (404)
```json
{
  "message": "Resource not found"
}
```

## C. Authentication Test Cases

| Test Case | Method | Endpoint | Headers | Body | Expected Status | Expected Response |
| --- | --- | --- | --- | --- | --- | --- |
| Login with valid credentials | POST | /login | Accept: application/json, Content-Type: application/json | {"email": "user1@wildlife.id", "password": "password"} | 200 | Success with token/user data |
| Login with invalid credentials | POST | /login | Accept: application/json, Content-Type: application/json | {"email": "invalid@test.com", "password": "wrong"} | 401 | Unauthenticated message |
| Login with missing fields | POST | /login | Accept: application/json, Content-Type: application/json | {} | 422 | Validation errors |
| Get authenticated user | GET | /me | Authorization: Bearer {token} | - | 200 | Current user data |
| Logout | POST | /logout | Authorization: Bearer {token} | - | 200 | Success message |

## D. Core Resource Test Cases

### Users
| Test Case | Method | Endpoint | Auth | Body | Expected Status |
| --- | --- | --- | --- | --- | --- |
| List all users | GET | /users | Required | - | 200 |
| Get user by ID | GET | /users/{user_id} | Required | - | 200/404 |
| Create user | POST | /users | Required | {"name": "Test User", "email": "test@wildlife.id", "password": "password123", "role": "konservasionis_lapangan"} | 201/422 |
| Update user | PUT/PATCH | /users/{user_id} | Required | {"name": "Updated Name"} | 200/404/422 |
| Delete user | DELETE | /users/{user_id} | Required | - | 200/404 |

### Roles
| Test Case | Method | Endpoint | Auth | Body | Expected Status |
| --- | --- | --- | --- | --- | --- |
| List roles with permissions | GET | /roles | Required | - | 200 |
| Get role by ID | GET | /roles/{role_id} | Required | - | 200/404 |
| Create role | POST | /roles | Required | {"role_name": "test_role", "description": "Test role"} | 201/422 |
| Sync role permissions | POST | /role-permissions/sync | Required | {"role_id": 1, "permission_ids": [1,2,3]} | 200 |

### Species
| Test Case | Method | Endpoint | Auth | Body | Expected Status |
| --- | --- | --- | --- | --- | --- |
| List species | GET | /species | Required | - | 200 |
| Get species with animals | GET | /species/{species_id} | Required | - | 200/404 |
| Create species | POST | /species | Required | {"common_name": "Test Species", "scientific_name": "Testus species", "conservation_status": "Vulnerable"} | 201/422 |

### Animals
| Test Case | Method | Endpoint | Auth | Body | Expected Status |
| --- | --- | --- | --- | --- | --- |
| List animals with species and device | GET | /animals | Required | - | 200 |
| Get animal details | GET | /animals/{animal_id} | Required | - | 200/404 |
| Create animal | POST | /animals | Required | {"species_id": 1, "name": "SATWA-999", "birth_date": "2024-01-01", "gender": "male", "is_active": true} | 201/422 |

### Tracking Devices
| Test Case | Method | Endpoint | Auth | Body | Expected Status |
| --- | --- | --- | --- | --- | --- |
| List devices | GET | /tracking-devices | Required | - | 200 |
| Get device with GPS readings | GET | /tracking-devices/{device_id} | Required | - | 200/404 |
| Create device | POST | /tracking-devices | Required | {"animal_id": 1, "model": "Lotek GPS-Plus", "device_condition": "normal", "is_active": true} | 201/422 |

### Geo Zones
| Test Case | Method | Endpoint | Auth | Body | Expected Status |
| --- | --- | --- | --- | --- | --- |
| List geo zones | GET | /geo-zones | Required | - | 200 |
| Create geo zone | POST | /geo-zones | Required | {"zone_name": "Zona Test", "zone_type": "koridor_ekologis", "center_lat": 3.65, "center_lon": 97.75} | 201/422 |

### GPS Readings
| Test Case | Method | Endpoint | Auth | Body | Expected Status |
| --- | --- | --- | --- | --- | --- |
| List GPS readings | GET | /gps-readings | Required | - | 200 |
| Create GPS reading | POST | /gps-readings | Required | {"device_id": 1, "latitude": 3.658620, "longitude": 97.731964, "recorded_at": "2026-10-19 10:00:00"} | 201/422 |

### Incident Reports
| Test Case | Method | Endpoint | Auth | Body | Expected Status |
| --- | --- | --- | --- | --- | --- |
| List incident reports | GET | /incident-reports | Required | - | 200 |
| Create incident report | POST | /incident-reports | Required | {"incident_type": "observasi", "location": "Zona Test", "description": "Test incident", "sync_status": "pending_sync"} | 201/422 |

### AI Media & Predictions
| Test Case | Method | Endpoint | Auth | Body | Expected Status |
| --- | --- | --- | --- | --- | --- |
| List AI media uploads | GET | /ai-media-uploads | Required | - | 200 |
| List AI predictions | GET | /ai-predictions | Required | - | 200 |
| Create prediction | POST | /ai-predictions | Required | {"media_id": 1, "model_version": "YOLOv8-v1", "processing_status": "queued", "verification_status": "pending"} | 201/422 |

### Notifications, Activity Log, Aggregate Reports
| Test Case | Method | Endpoint | Auth | Body | Expected Status |
| --- | --- | --- | --- | --- | --- |
| List notifications | GET | /notifications | Required | - | 200 |
| List activity log | GET | /activity-log | Required | - | 200 |
| List aggregate reports | GET | /aggregate-reports | Required | - | 200 |

## E. Testing Tips in Apidog

- Use environments to switch between local (`localhost:8000`) and production URLs.
- Save authentication tokens in environment variables and reference them with `{{token}}`.
- For cookie auth, enable "Send cookies" in request settings and complete CSRF flow first.
- Use pre-request scripts to automate token retrieval if needed.
- Validate response schemas using Apidog's assertion features.
- Test all CRUD operations for each resource to ensure full coverage.
