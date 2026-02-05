# Big Tech UI Design Guide - Component Patterns

## 🎨 Reusable Component Patterns

This guide provides copy-paste ready patterns for maintaining consistency across the application.

---

## 1. Form Field with Contextual Help

### Pattern: Text Input with Guidance
```vue
<div class="group">
  <!-- Label with dual information -->
  <label for="field-id" class="flex items-center justify-between text-sm font-semibold text-gray-700 mb-2">
    <span>
      Field Label 
      <span class="text-red-500">*</span> <!-- Add for required fields -->
    </span>
    <span class="text-xs font-normal text-gray-500">Context hint</span>
  </label>
  
  <!-- Input field -->
  <div class="relative">
    <input
      id="field-id"
      v-model="form.fieldName"
      type="text"
      maxlength="100"
      class="w-full px-5 py-4 text-lg border-2 border-gray-200 rounded-xl 
             text-gray-900 focus:ring-4 focus:ring-blue-500/10 
             focus:border-blue-500 transition-all placeholder:text-gray-400 
             bg-gray-50/50 focus:bg-white"
      placeholder="e.g., Helpful specific example here"
    />
    
    <!-- Optional: Character counter -->
    <div class="absolute right-4 top-1/2 -translate-y-1/2">
      <span :class="[
        'text-xs font-medium transition-colors',
        (form.fieldName?.length || 0) > 90 ? 'text-red-500' :
        (form.fieldName?.length || 0) > 75 ? 'text-yellow-600' : 'text-gray-400'
      ]">{{ form.fieldName?.length || 0 }} / 100</span>
    </div>
  </div>
  
  <!-- Helper text with icon -->
  <div class="mt-2 flex items-start gap-2">
    <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
    </svg>
    <p class="text-xs text-gray-600 leading-relaxed">
      <strong>Tip:</strong> Explain best practices, impact, or guidelines here.
    </p>
  </div>
  
  <!-- Error state -->
  <p v-if="form.errors.fieldName" class="mt-2 text-sm text-red-600 flex items-center gap-1">
    <XIcon class="w-4 h-4" />{{ form.errors.fieldName }}
  </p>
</div>
```

---

## 2. Select Dropdown with Enhanced Options

### Pattern: Select with Smart Defaults
```vue
<div>
  <label for="select-id" class="flex items-center justify-between text-sm font-semibold text-gray-700 mb-2">
    <span>
      Field Label 
      <span class="text-red-500">*</span>
    </span>
    <span class="text-xs font-normal text-gray-500">Purpose explanation</span>
  </label>
  
  <select
    id="select-id"
    v-model="form.fieldName"
    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl 
           text-gray-900 focus:ring-4 focus:ring-blue-500/10 
           focus:border-blue-500 transition-all bg-gray-50/50 
           focus:bg-white appearance-none cursor-pointer"
  >
    <option value="">-- Clear, actionable default text --</option>
    <option v-for="item in items" :key="item.id" :value="item.id">
      {{ item.name }}
    </option>
  </select>
  
  <!-- Success indicator -->
  <p v-if="form.fieldName && !form.errors.fieldName" 
     class="mt-1.5 text-xs text-emerald-600 font-medium flex items-center gap-1">
    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
    </svg>
    Helpful confirmation message
  </p>
  
  <!-- Error state -->
  <p v-if="form.errors.fieldName" class="mt-2 text-sm text-red-600">
    {{ form.errors.fieldName }}
  </p>
</div>
```

---

## 3. Price Input with Currency

### Pattern: Price Field with Calculations
```vue
<div>
  <label for="price" class="flex items-center justify-between text-sm font-semibold text-gray-700 mb-2">
    <span>
      Regular Price 
      <span class="text-red-500">*</span>
    </span>
    <span class="text-xs font-normal text-gray-500">Customer pays</span>
  </label>
  
  <div class="relative">
    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-semibold">৳</span>
    <input
      id="price"
      v-model="form.price"
      type="number"
      step="0.01"
      min="0"
      class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-xl 
             text-gray-900 focus:ring-4 focus:ring-blue-500/10 
             focus:border-blue-500 transition-all bg-gray-50/50 
             focus:bg-white font-semibold"
      placeholder="0.00"
    />
  </div>
  
  <!-- Auto-calculation display -->
  <p v-if="form.price && form.costPrice" 
     :class="[
       'mt-1.5 text-xs font-medium flex items-center gap-1',
       parseFloat(form.price) - parseFloat(form.costPrice) > 0 ? 'text-emerald-600' : 'text-gray-500'
     ]">
    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
      <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
    </svg>
    Profit margin: ৳{{ (parseFloat(form.price || 0) - parseFloat(form.costPrice || 0)).toFixed(2) }}
    <span v-if="parseFloat(form.price) > 0" class="text-xs">
      ({{ ((parseFloat(form.price || 0) - parseFloat(form.costPrice || 0)) / parseFloat(form.price) * 100).toFixed(1) }}%)
    </span>
  </p>
  
  <p v-if="form.errors.price" class="mt-2 text-sm text-red-600">
    {{ form.errors.price }}
  </p>
</div>
```

---

## 4. Card Section Header

### Pattern: Section Card with Icon
```vue
<div class="bg-white/70 backdrop-blur-sm rounded-2xl border border-gray-200/50 shadow-sm overflow-hidden">
  <!-- Header -->
  <div class="p-6 border-b border-gray-100">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <!-- Icon -->
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
          <IconComponent class="w-5 h-5 text-white" />
        </div>
        <!-- Title & Description -->
        <div>
          <h3 class="text-lg font-bold text-gray-900">Section Title</h3>
          <p class="text-sm text-gray-500">Brief description of what this section does</p>
        </div>
      </div>
      <!-- Optional: Action button or badge -->
      <button
        type="button"
        @click="handleAction"
        class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold 
               text-white bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl 
               hover:from-blue-700 hover:to-indigo-700 shadow-lg 
               shadow-blue-500/25 transition-all"
      >
        <PlusIcon class="w-4 h-4" />
        Action
      </button>
    </div>
  </div>
  
  <!-- Content -->
  <div class="p-6 space-y-6">
    <!-- Your form fields here -->
  </div>
</div>
```

---

## 5. Guidance Banner

### Pattern: Best Practices Banner
```vue
<div class="p-4 bg-gradient-to-r from-indigo-50 via-purple-50 to-pink-50 rounded-xl border border-purple-100">
  <div class="flex items-start gap-3">
    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center flex-shrink-0">
      <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
      </svg>
    </div>
    <div class="flex-1">
      <h4 class="text-sm font-semibold text-purple-900 mb-1">Banner Title</h4>
      <ul class="text-xs text-purple-700 space-y-1">
        <li class="flex items-start gap-2">
          <span class="text-purple-500 mt-0.5">•</span>
          <span><strong>Point 1:</strong> Explanation of best practice</span>
        </li>
        <li class="flex items-start gap-2">
          <span class="text-purple-500 mt-0.5">•</span>
          <span><strong>Point 2:</strong> Another helpful tip</span>
        </li>
        <li class="flex items-start gap-2">
          <span class="text-purple-500 mt-0.5">•</span>
          <span><strong>Point 3:</strong> Additional guidance</span>
        </li>
      </ul>
    </div>
  </div>
</div>
```

---

## 6. File Upload Zone

### Pattern: Drag & Drop Upload
```vue
<div class="relative">
  <div
    v-if="!preview"
    class="h-64 flex flex-col items-center justify-center border-2 border-dashed 
           border-gray-300 rounded-2xl hover:border-blue-500 hover:bg-blue-50/50 
           transition-all cursor-pointer bg-gray-50/50 group"
    @click="$refs.fileInput.click()"
    @dragover.prevent
    @drop.prevent="handleDrop"
  >
    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 
                flex items-center justify-center mb-4 group-hover:scale-110 
                transition-transform shadow-lg shadow-blue-500/25">
      <Upload class="h-8 w-8 text-white" />
    </div>
    <p class="text-base font-semibold text-gray-700 mb-1">
      Click to upload or drag & drop
    </p>
    <p class="text-sm text-gray-500 mb-1">Supported formats: JPG, PNG, WEBP</p>
    <p class="text-xs text-gray-400">Maximum file size: 5MB</p>
  </div>
  
  <!-- Preview with actions -->
  <div v-else class="relative group">
    <div class="h-72 w-full bg-gray-100 rounded-2xl overflow-hidden flex items-center justify-center">
      <img :src="preview" class="h-full w-full object-contain" alt="Preview" />
    </div>
    <div class="absolute top-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
      <button
        type="button"
        @click="$refs.fileInput.click()"
        class="px-3 py-2 bg-white/90 backdrop-blur-sm hover:bg-white 
               rounded-lg text-gray-700 font-medium text-sm flex items-center 
               gap-2 shadow-lg transition-all"
      >
        <Upload class="h-4 w-4" />
        Replace
      </button>
      <button
        type="button"
        @click="removeFile"
        class="px-3 py-2 bg-red-500 hover:bg-red-600 rounded-lg 
               text-white font-medium text-sm flex items-center gap-2 
               shadow-lg transition-all"
      >
        <XIcon class="h-4 w-4" />
        Remove
      </button>
    </div>
  </div>
  
  <input 
    ref="fileInput" 
    type="file" 
    @change="handleFileChange" 
    accept="image/*" 
    class="hidden" 
  />
</div>
```

---

## 7. Empty State

### Pattern: Friendly Empty State
```vue
<div class="text-center py-12 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
  <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
    <IconComponent class="h-8 w-8 text-gray-400" />
  </div>
  <p class="text-sm font-medium text-gray-700 mb-1">No items yet</p>
  <p class="text-xs text-gray-500 mb-4">
    Friendly explanation of what goes here and why it's empty
  </p>
  <button
    type="button"
    @click="handleAdd"
    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium 
           text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
  >
    <PlusIcon class="w-4 h-4" />
    Add First Item
  </button>
</div>
```

---

## 8. Status Badge

### Pattern: Dynamic Status Indicator
```vue
<span :class="[
  'inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold',
  status === 'active' || status === 'published'
    ? 'bg-green-100 text-green-800 border border-green-200'
    : status === 'pending'
    ? 'bg-yellow-100 text-yellow-800 border border-yellow-200'
    : 'bg-gray-100 text-gray-800 border border-gray-200'
]">
  <span :class="[
    'w-1.5 h-1.5 rounded-full mr-1.5',
    status === 'active' || status === 'published' ? 'bg-green-500' :
    status === 'pending' ? 'bg-yellow-500' : 'bg-gray-500'
  ]"></span>
  {{ status }}
</span>
```

---

## 9. Tooltip Icon

### Pattern: Contextual Help Icon
```vue
<div class="inline-flex items-center gap-1.5">
  <span class="text-sm font-medium text-gray-700">Field Label</span>
  <div class="group relative">
    <svg class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-help transition-colors" 
         fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
    </svg>
    <!-- Tooltip -->
    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 
                bg-gray-900 text-white text-xs rounded-lg opacity-0 
                group-hover:opacity-100 pointer-events-none transition-opacity 
                whitespace-nowrap shadow-lg">
      Helpful tooltip text here
      <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 
                  border-4 border-transparent border-t-gray-900"></div>
    </div>
  </div>
</div>
```

---

## 10. Progress Indicator

### Pattern: Form Progress Bar
```vue
<div class="space-y-2">
  <div class="flex justify-between items-center">
    <span class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Progress</span>
    <span class="text-sm font-bold text-gray-900">{{ Math.round(progress) }}%</span>
  </div>
  <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
    <div 
      class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full 
             transition-all duration-500 ease-out" 
      :style="{ width: progress + '%' }"
    ></div>
  </div>
  <p class="text-xs text-gray-500">{{ progressMessage }}</p>
</div>
```

---

## 🎨 Color System Reference

### Gradient Combinations
```css
/* Primary Actions */
from-blue-600 to-indigo-600

/* Success States */
from-emerald-500 to-teal-600

/* Warning States */
from-yellow-500 to-amber-600

/* Error States */
from-red-500 to-pink-600

/* Info/Media */
from-purple-500 to-pink-600

/* Neutral */
from-gray-500 to-slate-600
```

### Semantic Colors
```css
/* Success */
text-emerald-600, bg-emerald-50, border-emerald-200

/* Warning */
text-yellow-700, bg-yellow-50, border-yellow-200

/* Error */
text-red-600, bg-red-50, border-red-200

/* Info */
text-blue-700, bg-blue-50, border-blue-200

/* Neutral */
text-gray-600, bg-gray-50, border-gray-200
```

---

## 📏 Spacing System

```css
/* Field Margins */
mb-2   /* Label to input */
mt-1.5 /* Input to helper text */
mt-2   /* Input to error */
space-y-6 /* Between sections */

/* Card Padding */
p-6    /* Standard card padding */
p-4    /* Compact sections */
p-5    /* Medium sections */

/* Gaps */
gap-2  /* Tight elements */
gap-3  /* Normal elements */
gap-4  /* Loose elements */
gap-6  /* Section separators */
```

---

## 🔤 Typography Scale

```css
/* Headings */
text-3xl font-bold              /* Page title */
text-2xl font-bold              /* Section title */
text-lg font-bold               /* Card title */
text-base font-semibold         /* Subsection */

/* Body Text */
text-sm font-semibold           /* Labels */
text-sm font-medium             /* Important text */
text-sm                         /* Normal text */
text-xs                         /* Helper text */

/* Emphasis */
font-bold                       /* Strong emphasis */
font-semibold                   /* Medium emphasis */
font-medium                     /* Light emphasis */
```

---

## ✅ Accessibility Checklist

When implementing these patterns:

- [ ] Labels have `for` attribute matching input `id`
- [ ] Required fields marked with `*` and `aria-required="true"`
- [ ] Error messages use `aria-describedby` linking to field
- [ ] Color not the only indicator of state (use icons + text)
- [ ] Focus states clearly visible (ring utilities)
- [ ] Minimum touch target: 44x44px (mobile)
- [ ] Sufficient color contrast (WCAG AA minimum)
- [ ] Keyboard navigation works logically
- [ ] Screen reader tested with NVDA/VoiceOver

---

## 🚀 Performance Tips

1. **Use CSS Transitions** over JavaScript animations
2. **Lazy load** heavy components (CKEditor, etc.)
3. **Debounce** real-time validation (300-500ms)
4. **Optimize images** before upload
5. **Use `v-show`** for frequently toggled elements
6. **Use `v-if`** for conditionally rendered sections
7. **Memoize** expensive computations with `computed`
8. **Avoid** deep watchers when possible

---

*Copy these patterns to maintain consistency across your application.*

**Version:** 1.0  
**Last Updated:** February 5, 2026
