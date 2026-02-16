# Implementation Plan: Big Tech Style Attributes & Variants

## ✅ যা করতে হবে:

### 1. Attributes & Variants Tab Design (Screenshot Match)

#### **Tab Header:**
- Two tabs: "Attributes (2)" and "Variants (20)" with dynamic counts
- Active tab with blue bottom border
- Clean, minimal design

#### **Variants Tab Content:**

**Dynamic Price Calculation Info Cards (3 cards side-by-side):**
1. **Adjustment (±)** - Blue card
   - Base Price ± Amount
   - Example: $50 + $5 = $55
   
2. **Percentage (%)** - Green card  
   - Base Price × (1 ± %)
   - Example: $50 × 1.10 = $55
   
3. **Override Price** - Purple card
   - Fixed Custom Price
   - Example: Direct $45

**Variants Table:**
- Columns: Variant | SKU | Price Type | Value | Final Price | Stock | Image | Status
- Each row:
  - Variant: Attribute chips (e.g., Material: Cotton, Color: Red)
  - SKU: Text input field
  - Price Type: Dropdown (± A / % / Override)  
  - Value: Number input ($0)
  - Final Price: Calculated display ($0.00) - green bold
  - Stock: Number input (0)
  - Image: Upload placeholder icon
  - Status: Green "Active" button

#### **Features:**
- ✅ Base Price display at top
- ✅ Bulk Price Update button  
- ✅ Regenerate button
- ✅ Clean table layout
- ✅ Inline editing
- ✅ Real-time price calculation

---

## 📋 Implementation Steps:

1. ✅ Database migrations (DONE)
2. ✅ Models updated (DONE)
3. ⏳ Frontend UI redesign (IN PROGRESS)
4. ⏳ Backend API updates (PENDING)

---

## 🎯 Next Action:

Update ProductForm.vue Variants Tab section to match screenshot exactly.
