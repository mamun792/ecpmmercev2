# E-Commerce Project - Routing & Authentication Plan

## Project Overview
This is a **full-stack API-based e-commerce application** with:
- **Backend**: Laravel (PHP)
- **Frontend**: Vue.js with Inertia.js
- **Authentication**: JWT (JSON Web Tokens) + Laravel Sanctum
- **Architecture**: Monolithic (Frontend and Backend in same project)

---

## Current Project Structure Analysis

### Backend Stack
- Laravel Framework
- Spatie Permissions (Role-based access)
- JWT Authentication (Tymon\JWTAuth)
- Inertia.js Server-Side Rendering
- API Routes (api.php)
- Web Routes (web.php)

### Frontend Stack
- Vue 3
- Inertia.js Vue3 Adapter
- TailwindCSS + DaisyUI
- Vite Build Tool
- Vue Router (via Inertia)

### Current User Model
- Uses `Spatie\Permission\Traits\HasRoles` for role management
- Implements JWT authentication
- Fields: name, email, password, phone_number, address

---

## Routing Architecture Plan

### 1. HOME PAGE ROUTE
**Route**: `/`  
**Purpose**: Landing/Welcome page for all visitors  
**View**: `resources/js/Pages/Welcome.vue`  
**Behavior**:
- Display "Welcome" message
- Show public e-commerce content (featured products, banners, etc.)
- No authentication required
- Show links to login/register for guests
- Show user-specific content for authenticated users

**Implementation Notes**:
- Remove current redirect middleware from `/` route
- Make it a public route
- Add conditional rendering based on auth state

---

### 2. ADMIN LOGIN ROUTE
**Route**: `/admin/login`  
**Method**: GET (show form), POST (process login)  
**Purpose**: Admin authentication endpoint  
**View**: `resources/js/Pages/Auth/AdminLogin.vue`  
**Behavior**:
- Separate login form for admin users
- Check user role after authentication
- Only users with admin/super-admin role can access
- Redirect to `/admin/dashboard` on success
- Return error if non-admin tries to login

**Middleware**:
- `guest` (for GET request)
- Rate limiting for security

**Authentication Flow**:
```
1. Admin enters credentials at /admin/login
2. Backend validates credentials
3. Check if user has admin role (using Spatie permissions)
4. If admin role exists: Generate token → Redirect to admin dashboard
5. If no admin role: Return error "Unauthorized - Admin access only"
```

---

### 3. USER LOGIN ROUTE
**Route**: `/login`  
**Method**: GET (show form), POST (process login)  
**Purpose**: Customer/User authentication endpoint  
**View**: `resources/js/Pages/Auth/Login.vue`  
**Behavior**:
- Login form for regular customers
- Authenticate user credentials
- Redirect to `/user/dashboard` on success
- Available for non-admin users

**Middleware**:
- `guest` (for GET request)
- Rate limiting for security

**Authentication Flow**:
```
1. User enters credentials at /login
2. Backend validates credentials
3. Check user role
4. If regular user (no admin role): Generate token → Redirect to user dashboard
5. If admin role: Suggest using /admin/login instead
```

---

### 4. USER REGISTER ROUTE
**Route**: `/register`  
**Method**: GET (show form), POST (process registration)  
**Purpose**: New customer registration  
**View**: `resources/js/Pages/Auth/Register.vue`  
**Behavior**:
- Registration form for new users
- Create new user account
- Assign default "customer" role
- Auto-login after registration (optional)
- Redirect to `/user/dashboard` or `/login` after success

**Required Fields**:
- Name
- Email (unique)
- Password (with confirmation)
- Phone Number
- Address (optional)

**Middleware**:
- `guest` (for GET request)

**Registration Flow**:
```
1. User fills registration form
2. Validate input (email unique, password strength, etc.)
3. Create user record in database
4. Assign default "customer" or "user" role
5. Send welcome email (optional)
6. Option A: Auto-login and redirect to /user/dashboard
7. Option B: Redirect to /login with success message
```

---

## Dashboard Routes

### 5. ADMIN DASHBOARD
**Route**: `/admin/dashboard`  
**Purpose**: Admin control panel  
**View**: `resources/js/Pages/Admin/Dashboard.vue`  
**Behavior**:
- Only accessible by authenticated admin users
- Show admin-specific metrics, statistics
- Quick access to admin features (products, orders, users, etc.)

**Middleware**:
- `auth` (must be logged in)
- `role:admin|super-admin` (Spatie permission check)
- `check.route.permission` (existing middleware)

**Features to Display**:
- Total orders, revenue, customers
- Recent orders
- Low stock alerts
- Quick actions (add product, view orders, etc.)

---

### 6. USER DASHBOARD
**Route**: `/user/dashboard`  
**Purpose**: Customer account panel  
**View**: `resources/js/Pages/User/Dashboard.vue` (to be created)  
**Behavior**:
- Only accessible by authenticated users
- Show user-specific data
- User cannot access admin routes

**Middleware**:
- `auth` (must be logged in)
- `role:customer|user` (only regular users)

**Features to Display**:
- User profile information
- Order history
- Wishlist
- Address book
- Account settings
- Loyalty points/rewards (if applicable)

---

## Authentication Logic

### Role-Based Redirection System

#### After Login (Admin Route `/admin/login`):
```php
if (user has 'admin' or 'super-admin' role) {
    redirect to '/admin/dashboard'
} else {
    return error "You don't have admin privileges"
}
```

#### After Login (User Route `/login`):
```php
if (user has 'admin' or 'super-admin' role) {
    return message "Please use admin login at /admin/login"
} else {
    redirect to '/user/dashboard'
}
```

#### Automatic Role Assignment:
- **On Registration**: Assign "customer" or "user" role by default
- **Admin Creation**: Created manually via seeder or admin panel with "admin" role

---

## API Routes (for Frontend consumption)

All API routes should be prefixed with `/api/`

### Authentication API Endpoints:
```
POST /api/auth/register          - User registration
POST /api/auth/login             - User/Customer login
POST /api/auth/admin-login       - Admin login
POST /api/auth/logout            - Logout (clear token)
POST /api/auth/refresh           - Refresh JWT token
GET  /api/auth/me                - Get authenticated user data
```

### Protected API Endpoints:
```
// User Routes (requires 'user' or 'customer' role)
GET  /api/user/profile           - Get user profile
PUT  /api/user/profile           - Update user profile
GET  /api/user/orders            - Get user order history
GET  /api/user/wishlist          - Get user wishlist

// Admin Routes (requires 'admin' role)
GET  /api/admin/dashboard-stats  - Dashboard statistics
GET  /api/admin/users            - List all users
GET  /api/admin/orders           - List all orders
... (existing admin API routes)
```

---

## Middleware Configuration

### Middleware to Implement:

1. **EnsureUserRole** - Check if user has specific role
2. **RedirectIfAuthenticated** - Redirect logged-in users away from login/register
3. **AdminOnly** - Ensure only admins can access route
4. **UserOnly** - Ensure only regular users can access route

### Middleware Groups:
```php
// For admin routes
Route::middleware(['auth', 'role:admin|super-admin'])->group(...)

// For user routes
Route::middleware(['auth', 'role:customer|user'])->group(...)

// For public routes
Route::middleware(['guest'])->group(...)
```

---

## File Structure Changes Required

### New Files to Create:

#### Controllers:
```
app/Http/Controllers/Auth/
├── AdminAuthController.php         (Admin login logic)
├── UserAuthController.php          (User login logic - if separating)
└── RegisterController.php          (May already exist)

app/Http/Controllers/User/
└── UserDashboardController.php     (User dashboard)
```

#### Views (Vue Components):
```
resources/js/Pages/
├── Welcome.vue                      (Update existing)
├── Auth/
│   ├── AdminLogin.vue              (New - Admin login form)
│   ├── Login.vue                   (Update - User login form)
│   └── Register.vue                (Update - User registration)
└── User/
    └── Dashboard.vue               (New - User dashboard)
```

#### Middleware:
```
app/Http/Middleware/
├── EnsureUserRole.php              (Role-based access)
├── AdminOnly.php                   (Admin access only)
└── UserOnly.php                    (User access only)
```

---

## Route Definitions (Proposed)

### web.php:
```php
// Public Home
Route::get('/', [HomeController::class, 'index'])->name('home');

// Guest routes (not authenticated)
Route::middleware('guest')->group(function () {
    // User Authentication
    Route::get('/login', [AuthController::class, 'showUserLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'userLogin']);
    
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    
    // Admin Authentication
    Route::get('/admin/login', [AdminAuthController::class, 'showAdminLogin'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'adminLogin']);
});

// Authenticated User Routes
Route::middleware(['auth', 'role:customer|user'])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
    // ... other user routes
});

// Admin Routes (existing structure, but with prefix)
Route::middleware(['auth', 'role:admin|super-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // ... all existing admin routes
});
```

---

## Database Considerations

### Roles Table (Spatie Permissions):
Ensure these roles exist in your database:
- `admin` or `super-admin`
- `customer` or `user`

### Seeder Update Required:
```php
// database/seeders/RoleSeeder.php
- Create default roles: admin, customer
- Assign permissions to each role

// database/seeders/UserSeeder.php
- Create default admin user with admin role
- Create test customer user with customer role
```

---

## Security Considerations

1. **CSRF Protection**: Enabled for all POST routes
2. **Rate Limiting**: Apply to login/register routes (prevent brute force)
3. **Password Validation**: Minimum 8 characters, complexity rules
4. **Email Verification**: Optional but recommended
5. **JWT Token Expiry**: Set appropriate expiration time
6. **Role Verification**: Always verify role on backend, never trust frontend

---

## Frontend State Management

### Auth State (to store in Inertia shared data):
```javascript
{
    user: {
        id: 1,
        name: "John Doe",
        email: "john@example.com",
        roles: ["customer"],
        permissions: [...]
    },
    isAuthenticated: true,
    isAdmin: false,
    isUser: true
}
```

### Navigation Logic:
- If `isAdmin === true`: Show admin menu, hide user menu
- If `isUser === true`: Show user menu, hide admin menu
- If `isAuthenticated === false`: Show login/register links

---

## Testing Scenarios

### Test Cases to Cover:

1. **Guest Access**:
   - [ ] Can access home page
   - [ ] Can access login page
   - [ ] Can access register page
   - [ ] Cannot access admin/user dashboards (redirect to login)

2. **User Login**:
   - [ ] Successful login redirects to /user/dashboard
   - [ ] Failed login shows error message
   - [ ] Admin trying user login gets appropriate message

3. **Admin Login**:
   - [ ] Successful admin login redirects to /admin/dashboard
   - [ ] Regular user trying admin login gets error
   - [ ] Failed login shows error message

4. **User Registration**:
   - [ ] Successful registration creates user with 'customer' role
   - [ ] Duplicate email shows error
   - [ ] Validation errors display correctly

5. **Dashboard Access**:
   - [ ] Admin can access /admin/dashboard
   - [ ] User can access /user/dashboard
   - [ ] Admin cannot access /user/dashboard
   - [ ] User cannot access /admin/dashboard

6. **Logout**:
   - [ ] Logout clears session/token
   - [ ] Redirects to home page
   - [ ] Cannot access protected routes after logout

---

## Implementation Steps (Recommended Order)

### Phase 1: Setup & Configuration
1. Update User model and ensure Spatie roles are properly configured
2. Create/update role seeder (admin, customer roles)
3. Run migrations and seeders to create roles
4. Create middleware for role checking

### Phase 2: Backend Routes & Controllers
5. Create AdminAuthController
6. Update existing AuthController for user login
7. Create UserDashboardController
8. Update web.php routes as per plan
9. Update api.php routes for API endpoints

### Phase 3: Frontend Components
10. Update Welcome.vue (home page)
11. Create/Update Login.vue (user login)
12. Create AdminLogin.vue (admin login)
13. Update Register.vue (user registration)
14. Create User/Dashboard.vue (user dashboard)
15. Update Admin/Dashboard.vue if needed

### Phase 4: Integration & Testing
16. Test all routes with different user roles
17. Implement rate limiting on auth routes
18. Add proper error handling and validation messages
19. Test JWT token generation and validation
20. Ensure proper redirections based on roles

### Phase 5: Polish & Security
21. Add CSRF protection verification
22. Implement email verification (optional)
23. Add "Remember Me" functionality
24. Add password reset flow
25. Security audit and penetration testing

---

## Additional Features (Nice to Have)

1. **Social Login**: Google, Facebook OAuth
2. **Two-Factor Authentication (2FA)**: For admin users
3. **Login History**: Track user login attempts
4. **Session Management**: View active sessions
5. **Account Lockout**: After multiple failed attempts
6. **Email Notifications**: On login from new device
7. **Guest Checkout**: For non-registered users

---

## Notes & Recommendations

1. **Keep It Simple**: Start with basic auth, add features incrementally
2. **Security First**: Always validate on backend, sanitize inputs
3. **User Experience**: Clear error messages, loading states
4. **Mobile Responsive**: Ensure all pages work on mobile devices
5. **API Documentation**: Document all API endpoints (consider using Swagger)
6. **Error Logging**: Log authentication failures for security monitoring
7. **Performance**: Cache user roles/permissions to reduce DB queries

---

## Summary

This plan outlines a complete routing and authentication system for your e-commerce application with:

- **Public Home** at `/` showing welcome content
- **Admin Login** at `/admin/login` → redirects to `/admin/dashboard`
- **User Login** at `/login` → redirects to `/user/dashboard`
- **User Registration** at `/register` → creates customer account
- **Role-based Access Control** using Spatie Permissions
- **JWT Authentication** for API security
- **Separate Dashboards** for admin and users

The architecture maintains your existing Laravel + Inertia + Vue setup while adding clear separation between admin and user flows.

---

## Next Steps

Once you approve this plan:
1. Review and confirm the routing structure
2. Confirm role names (admin, customer, user, super-admin)
3. Begin implementation following the phases outlined above
4. Create tasks/tickets for each implementation phase

---

**Document Version**: 1.0  
**Created**: January 10, 2026  
**Status**: Awaiting Approval
