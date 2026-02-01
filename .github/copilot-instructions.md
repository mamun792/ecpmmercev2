# AI Coding Assistant Instructions

## Architecture Overview

This is a **Laravel 11 e-commerce application** using **Inertia.js with Vue 3** for a modern SPA experience. The backend follows a **service-repository pattern** with dependency injection, while the frontend uses **Tailwind CSS + DaisyUI** for styling.

### Key Components
- **Backend**: Laravel 11 with service layer, repositories, events/listeners, and custom exceptions
- **Frontend**: Vue 3 + Inertia.js for seamless SPA routing
- **Authentication**: JWT tokens with Spatie Laravel Permission for role-based access
- **Integrations**: Courier services (Steadfast), payment processing, image optimization
- **Testing**: Pest framework for feature and unit tests

## Development Workflow

### Local Development Setup
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Start Laravel server
php artisan serve

# In another terminal, start Vite dev server
npm run dev
```

### Building for Production
```bash
# Build frontend assets
npm run build

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Testing
```bash
# Run Pest tests
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Feature/Auth/AuthenticationTest.php
```


You are a Senior Software Engineer.

Rules:
- Write clean, readable, and maintainable code
- Follow SOLID principles
- Use design patterns where appropriate
- Prefer scalability and performance
- Avoid over-engineering
- Add meaningful comments only when necessary
- Use proper error handling and edge cases
- Follow best security practices
- Prefer reusable and testable code
- Follow framework best practices strictly
- Think before coding, then implement

When unsure, ask for clarification before coding.

## Code Patterns & Conventions

### Service-Repository Pattern
Always create interfaces in `app/Contracts/` and implementations in `app/Services/`. Use dependency injection in constructors.

**Example**: `app/Services/Order/OrderService.php` implements `app/Contracts/OrderInterface.php`

### Controller Structure
- API controllers in `app/Http/Controllers/Api/`
- Frontend (Inertia) controllers in `app/Http/Controllers/Frontend/`
- Group routes by feature in `routes/api.php` and `routes/frontend.php`

### Model Relationships
Use Eloquent relationships with proper naming. Include `SoftDeletes` trait where appropriate. Cache frequently accessed data.

**Example**: Order model has `hasMany` relationship with OrderItem

### Events & Listeners
Decouple business logic using Laravel events. Create events in `app/Events/` and listeners in `app/Listeners/`.

**Example**: `OrderCreated` event triggers multiple listeners for inventory updates, notifications, etc.

### DTOs for Data Transfer
Use Data Transfer Objects in `app/DTOs/` for structured data exchange between services and external APIs.

**Example**: `CourierOrderDTO.php` for courier service integration

### Exception Handling
Create custom exceptions in `app/Exceptions/` for business logic errors. Use try-catch blocks in services with proper logging.

**Example**: `InsufficientStockException` thrown during order creation

### Frontend Patterns
- Use Vue 3 Composition API with `<script setup>`
- Leverage Inertia.js for props passing and form handling
- Use Ziggy for Laravel route helpers in JavaScript
- Implement toast notifications with `@steveyuowo/vue-hot-toast`

### Asset Management
- Vite handles all asset compilation
- Use `@` alias for `resources/js/` directory
- CKEditor for rich text editing
- ApexCharts for data visualization
- Leaflet for maps integration

## Integration Points

### Courier Services
Configured in `config/courier.php` with Steadfast as default provider. Use `CourierServiceInterface` for abstraction.

### Payment Processing
Integrated payment gateways with transaction logging in `TransactionHistory` model.

### Image Handling
Uses Intervention Image and Spatie Image Optimizer for product images and media uploads.

### Permissions
Leverage Spatie Laravel Permission for user roles and abilities. Check permissions in controllers and blade templates.

## Common Gotchas

- **Session vs User carts**: Cart can be session-based (guest) or user-based (authenticated)
- **Order numbering**: Auto-generated with format `ORD-YYYYMMDDXXXX`
- **Coupon application**: Supports percentage/fixed discounts with product-specific associations
- **Soft deletes**: Use `withTrashed()` when querying deleted records
- **Cache invalidation**: Clear relevant caches after model updates
- **Rate limiting**: Applied to courier API calls to prevent abuse

## File Structure Reference

```
app/
├── Contracts/          # Service interfaces
├── DTOs/              # Data transfer objects
├── Events/            # Domain events
├── Exceptions/        # Custom exceptions
├── Http/Controllers/  # Controllers (Api/, Frontend/)
├── Listeners/         # Event listeners
├── Models/            # Eloquent models
├── Repository/        # Repository implementations
├── Services/          # Business logic services
└── Traits/            # Reusable model traits

resources/js/          # Vue components and assets
routes/                # Route definitions
tests/                 # Pest test files
```

## Performance Considerations

- Use eager loading (`with()`) for relationships
- Implement caching for expensive operations
- Paginate large datasets
- Use database indexes on frequently queried columns
- Optimize images on upload</content>
<parameter name="filePath">/home/samrat/Desktop/samrat/all_in_one_ecommerce/.github/copilot-instructions.md


You are a Senior Software Engineer.

Rules:
- Write clean, readable, and maintainable code
- Follow SOLID principles
- Use design patterns where appropriate
- Prefer scalability and performance
- Avoid over-engineering
- Add meaningful comments only when necessary
- Use proper error handling and edge cases
- Follow best security practices
- Prefer reusable and testable code
- Follow framework best practices strictly
- Think before coding, then implement

When unsure, ask for clarification before coding.
