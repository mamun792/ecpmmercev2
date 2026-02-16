# 🎯 Attributes & Variants UI Update - Implementation Guide

## ✅ Database & Backend: DONE

- ✅ Migrations run সফল
- ✅ Models updated
- ✅ Dynamic pricing columns added (`price_type`, `price_value`)
- ✅ Global attributes support added

## 📝 Frontend Changes Needed

### Location: `resources/js/Components/Product/ProductForm.vue`

### 1. Variables Added (✅ DONE):
```javascript
const attributesVariantsTab = ref('variants');
const showBulkPriceModal = ref(false);
```

### 2. Tab Structure (✅ PARTIALLY DONE):

**Current:** Separate tabs in sidebar navigation  
**Needed:** Combined "Attributes & Variants" section with sub-tabs

**Implemented:**
- Two sub-tabs: "Attributes (X)" and "Variants (Y)"  
- Tab switching works
- Dynamic counts showing

### 3. Variants Table Structure (⏳ NEEDED):

Screenshot থেকে দেখছি table এ এই columns চাই:

| Variant | SKU | Price Type | Value | Final Price | Stock | Image | Status |
|---------|-----|------------|-------|-------------|-------|-------|--------|
| Material: Cotton<br>Color: Red | SKU input | ± A dropdown | $ input | $0.00 (green) | input | 📷 | Active |

**Current Implementation:** Card-based layout  
**Needed:** Table-based layout (screenshot এর মতো)

---

## 🚀 Quick Fix Required:

আপনার screenshots এ যে table layout দেখাচ্ছে, সেটা implement করতে হলে:

### Option 1: Simple Table (Recommended)
ProductForm.vue এর variants section এ:
```vue
<div class="overflow-x-auto">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Variant</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">SKU</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Price Type</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Value</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Final Price</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Stock</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Image</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(variation, index) in form.variations" :key="index" class="border-b hover:bg-gray-50">
                <!-- Variant chips -->
                <td class="px-4 py-3">
                    <div class="flex flex-wrap gap-1">
                        <span v-for="attr in variation.attributes" :key="attr.attribute_value_id"
                              class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded">
                            {{ attr.attribute_name }}: {{ attr.attribute_value_label }}
                        </span>
                    </div>
                </td>
                
                <!-- SKU -->
                <td class="px-4 py-3">
                    <input v-model="variation.sku" type="text" placeholder="SKU"
                           class="w-full px-2 py-1 border rounded text-sm" />
                </td>
                
                <!-- Price Type Dropdown -->
                <td class="px-4 py-3">
                    <select v-model="variation.price_type" 
                            class="w-full px-2 py-1 border rounded text-sm">
                        <option value="adjustment">± A</option>
                        <option value="percentage">%</option>
                        <option value="override">Override</option>
                    </select>
                </td>
                
                <!-- Value -->
                <td class="px-4 py-3">
                    <div class="relative">
                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                        <input v-model="variation.price_value" type="number" step="0.01"
                               class="w-full pl-6 pr-2 py-1 border rounded text-sm" placeholder="0" />
                    </div>
                </td>
                
                <!-- Final Price (calculated) -->
                <td class="px-4 py-3">
                    <span class="text-lg font-bold text-emerald-600">
                        ${{ calculateFinalPrice(variation).toFixed(2) }}
                    </span>
                </td>
                
                <!-- Stock -->
                <td class="px-4 py-3">
                    <input v-model.number="variation.stock" type="number"
                           class="w-full px-2 py-1 border rounded text-sm" placeholder="0" />
                </td>
                
                <!-- Image -->
                <td class="px-4 py-3">
                    <button @click="$refs['varImg-' + index][0].click()" type="button"
                            class="w-10 h-10 flex items-center justify-center border-2 border-dashed rounded">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </button>
                    <input :ref="'varImg-' + index" type="file" class="hidden"
                           @change="handleVariationImageUpload($event, index)" accept="image/*" />
                </td>
                
                <!-- Status -->
                <td class="px-4 py-3">
                    <button @click="variation.status = variation.status === 'active' ? 'inactive' : 'active'"
                            :class="variation.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                            class="px-3 py-1 text-xs font-semibold rounded">
                        {{ variation.status === 'active' ? 'Active' : 'Inactive' }}
                    </button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

### Helper Function:
```javascript
const calculateFinalPrice = (variation) => {
    const basePrice = parseFloat(form.price || 0);
    const value = parseFloat(variation.price_value || 0);
    
    switch(variation.price_type) {
        case 'adjustment':
            return basePrice + value;
        case 'percentage':
            return basePrice * (1 + value/100);
        case 'override':
            return value;
        default:
            return basePrice;
    }
};
```

---

## ⚡ Action Items:

1. **ProductForm.vue এ Navigate করুন**
2. **Line 2900-3200 খুঁজুন** (Variants section)
3. **Card-based layout টা replace করুন** table layout দিয়ে (উপরের code)
4. **calculateFinalPrice function add করুন** (script section এ)
5. **Test করুন** browser এ

---

## 📦 Current Status:

✅ Backend Ready  
✅ Database Ready  
✅ Models Ready  
⏳ Frontend UI (In Progress - 60%)  
⏳ Table Layout Needed  

---

**Next Step:** আপনি কি চান আমি সম্পূর্ণ table implementation টা করে দিই? নাকি আপনি নিজে করবেন?
