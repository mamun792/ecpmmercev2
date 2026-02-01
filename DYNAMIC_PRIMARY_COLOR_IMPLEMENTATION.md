# Dynamic Primary Color Implementation Guide

This guide outlines the steps to make the website's primary color dynamic, allowing it to be changed from the backend (admin panel) without code modifications.

## 1. Database & Backend Setup

### 1.1 Create Migration

Create a migration to add the `primary_color` column to the `general_settings` table.

```bash
php artisan make:migration add_primary_color_to_general_settings_table --table=general_settings
```

In the migration file:

```php
public function up()
{
    Schema::table('general_settings', function (Blueprint $table) {
        $table->string('primary_color')->default('#f0512e')->after('app_name'); // Default to current orange
    });
}

public function down()
{
    Schema::table('general_settings', function (Blueprint $table) {
        $table->dropColumn('primary_color');
    });
}
```

### 1.2 Update Model

Update `app/Models/GeneralSetting.php` to include `primary_color` in the `$fillable` array.

```php
protected $fillable = [
    // ... existing fields
    'primary_color',
];
```

### 1.3 Share Data via Inertia

Ensure the settings are shared globally so they are available in the frontend layout. Check `app/Http/Middleware/HandleInertiaRequests.php`.

```php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        // Ensure general_settings is available here
        'general_settings' => fn () => GeneralSetting::first(),
    ]);
}
```

## 2. Tailwind Configuration

Update `tailwind.config.js` to use a CSS variable for the primary color instead of a hardcoded hex value.

```javascript
// tailwind.config.js
export default {
    // ...
    theme: {
        extend: {
            colors: {
                // Use CSS variable with fallback
                primary: "var(--primary-color)",
            },
            // ...
        },
    },
    // ...
};
```

_Note: Using `var(--primary-color)` works best for solid colors. If you need opacity modifiers (e.g., `bg-primary/50`), you will need to store the color as RGB numbers and use `rgb(var(--primary-rgb) / <alpha-value>)`. For simplicity, we will stick to the hex/var approach first._

## 3. Frontend Implementation

### 3.1 Update Frontend Layout

Modify `resources/js/Layouts/FrontendLayout.vue` to apply the color from the shared props to the root element.

```vue
<script setup>
import { computed, onMounted, watch } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const generalSettings = computed(() => page.props.general_settings);

const setPrimaryColor = () => {
    const color = generalSettings.value?.primary_color || "#f0512e";
    document.documentElement.style.setProperty("--primary-color", color);
};

// Set initially
onMounted(() => {
    setPrimaryColor();
});

// Watch for changes (if using a live preview or meaningful SPA navigation)
watch(generalSettings, () => {
    setPrimaryColor();
});
</script>

<template>
    <!-- Your layout content -->
    <slot />
</template>
```

### 3.2 Add Default Variable

In `resources/css/app.css`, add a default value for the variable as a fallback.

```css
:root {
    --primary-color: #f0512e; /* Default Orange */
}
```

## 4. Admin Panel Update

You will need to add a color picker input to your Admin Settings page (`resources/js/Pages/Admin/Settings/Index.vue` or equivalent).

```vue
<!-- Example Input -->
<div>
    <InputLabel for="primary_color" value="Primary Color" />
    <TextInput
        id="primary_color"
        type="color"
        v-model="form.primary_color"
        class="mt-1 block w-full h-10 p-1"
    />
    <InputError class="mt-2" :message="form.errors.primary_color" />
</div>
```

## Summary

1.  **Database**: Store the color.
2.  **Tailwind**: Point `primary` to `var(--primary-color)`.
3.  **Layout**: Inject the database value into the `:root` style tag using JavaScript.
