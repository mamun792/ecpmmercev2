# Admin Order Card - Visual Component Guide

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ 🔴 HIGH PRIORITY RING (conditional)                                        │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ HEADER SECTION                                                          │ │
│ │ ┌──────────────────┬────────────────────────────┬────────────────────┐  │ │
│ │ │ ORD-20240101001  │ 📝 Admin Note Indicator    │ 2h ago       HIGH  │  │ │
│ │ │ [NEW TODAY]      │                             │ 10:30 AM           │  │ │
│ │ └──────────────────┴────────────────────────────┴────────────────────┘  │ │
│ │ ⚠️ High Value  ⚠️ COD  ⚠️ New Customer                               │ │
│ ├─────────────────────────────────────────────────────────────────────────┤ │
│ │                                                                         │ │
│ │ MAIN CONTENT GRID (3 columns)                                          │ │
│ │ ┌──────────────────┬──────────────────┬──────────────────────────────┐  │ │
│ │ │ 👤 CUSTOMER      │ 📦 ORDER DETAILS │ 🚚 FULFILLMENT              │  │ │
│ │ ├──────────────────┼──────────────────┼──────────────────────────────┤  │ │
│ │ │ John Doe         │ [Pending] [Paid] │ Steadfast                   │  │ │
│ │ │ [First Order]    │                  │ ID: ST-123456               │  │ │
│ │ │                  │ ৳15,000          │ 🔗 Track Package            │  │ │
│ │ │ 📞 01712345678   │ Payment: COD     │                             │  │ │
│ │ │ ✉️  john@mail    │ Items: 3         │ ✅ Address Verified         │  │ │
│ │ │ 📍 Dhaka, Banga  │                  │                             │  │ │
│ │ │                  │ ┌─────────────┐  │ ┌─────────────────────────┐  │  │ │
│ │ │ 💬 Customer Note │ │ [IMG] Prod1 │  │ │ [👁️ View Details]       │  │ │
│ │ │ "Please deliver  │ │ Qty: 2      │  │ │ [✏️ Edit] [🗑️ Delete]   │  │ │
│ │ │  before 5pm"     │ │ +2 more     │  │ └─────────────────────────┘  │  │ │
│ │ │                  │ └─────────────┘  │                             │  │ │
│ │ └──────────────────┴──────────────────┴──────────────────────────────┘  │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘

PRIORITY LEVELS:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🔴 HIGH (Red Ring + Badge)
   ├─ 3+ risk factors
   ├─ Pulsing animation
   └─ Red border: 2px

🟡 MEDIUM (Amber Ring + Badge)
   ├─ 1-2 risk factors
   ├─ Amber border: 1px
   └─ No animation

🔵 LOW (Standard)
   ├─ No risk factors
   ├─ Gray border
   └─ Clean design


RISK INDICATORS:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

⚠️ High Value      → Order total > ৳10,000
⚠️ COD             → Cash on Delivery payment
⚠️ New Customer    → First order from customer
⚠️ Payment Overdue → Unpaid for multiple days


BADGES & INDICATORS:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[NEW TODAY]        → Green pulsing badge for today's orders
[First Order]      → Purple badge for new customers
📝                 → Blue circle with FileText icon (admin notes)
✅                 → Green checkmark (address verified)
🔗                 → Blue link (track package)


COLOR SCHEME:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Headers:
  • Gradient: from-gray-50 to-white
  • Border: border-gray-100

Sections:
  • Background: bg-gray-50
  • Border: rounded-xl

Order Number:
  • Gradient: from-emerald-500 to-emerald-600
  • Text: White
  • Shadow: shadow-sm

Status Badges:
  • Pending:    bg-amber-100   text-amber-700
  • Processing: bg-blue-100    text-blue-700
  • Completed:  bg-green-100   text-green-700
  • Cancelled:  bg-red-100     text-red-700

Payment Badges:
  • Paid:       bg-green-100   text-green-700
  • Unpaid:     bg-red-100     text-red-700
  • COD:        bg-orange-100  text-orange-700

Buttons:
  • View:   bg-blue-600   hover:bg-blue-700
  • Edit:   bg-amber-100  hover:bg-amber-200
  • Delete: bg-red-100    hover:bg-red-200
```

---

# Quick Stats Overview - Visual Layout

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ STATISTICS CARDS (6 columns, responsive)                                   │
│ ┌──────────┬──────────┬──────────┬──────────┬──────────┬──────────┐        │
│ │ 📅 TODAY │ 💰 TODAY │ ⏰ PEND  │ 📈 HIGH  │ ⚠️ UNPAID│ ⚡ RISK  │        │
│ │ ┌──────┐ │ ┌──────┐ │ ┌──────┐ │ ┌──────┐ │ ┌──────┐ │ ┌──────┐ │        │
│ │ │  25  │ │ │৳45K  │ │ │  12  │ │ │  8   │ │ │  5   │ │ │  3   │ │        │
│ │ └──────┘ │ └──────┘ │ └──────┘ │ └──────┘ │ └──────┘ │ └──────┘ │        │
│ │ Today's  │ Today's  │ Pending  │ High Val │ Unpaid   │ Risk     │        │
│ │ Orders   │ Revenue  │ Orders   │ Orders   │ Orders   │ Orders   │        │
│ │ +15% ↗️  │ Avg:৳1.8K│ Needs    │ >৳5,000  │ Follow   │ COD+High │        │
│ │ vs yest  │          │ attention│          │ up       │ /New     │        │
│ └──────────┴──────────┴──────────┴──────────┴──────────┴──────────┘        │
│                                                                             │
│ QUICK ACTIONS BAR (Gradient Orange Background)                             │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ Quick Admin Actions                                         [Buttons]  │ │
│ │ Manage your orders efficiently                                          │ │
│ │                                                                         │ │
│ │ [⏰ Process 12 Pending] [⚠️ Follow-up 5 Unpaid]                         │ │
│ │ [📦 Bulk Ship Orders]  [👥 Customer Reports]                           │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘


STAT CARD FEATURES:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✨ Animations:
   • Pulse: Today's Orders (if new)
   • Hover: -translate-y-1 + shadow-lg
   • Urgent: Pulsing red indicator

🎨 Color Themes:
   • Blue:   Today's Orders, Pending
   • Green:  Revenue, Completed
   • Purple: High Value
   • Red:    Unpaid (if >5)
   • Orange: Risk Orders

📊 Trend Indicators:
   • ↗️ Green arrow: Positive growth
   • ↘️ Red arrow: Negative growth
   • Percentage display

🚨 Urgent States:
   • Pending > 10:  Urgent amber background
   • Unpaid > 5:    Urgent red background
   • Risk > 3:      Urgent orange background
   • White text when urgent


ACTION BUTTONS:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

States:
  • Normal:  white bg-opacity-20
  • Hover:   white bg-opacity-30
  • Blur:    backdrop-blur-sm

Conditional Display:
  • Process button:  Only if pendingOrders > 0
  • Follow-up button: Only if unpaidOrders > 0
  • Bulk operations: Always visible


RESPONSIVE BREAKPOINTS:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Mobile (sm):     grid-cols-2
Tablet (lg):     grid-cols-3
Desktop (xl):    grid-cols-6

Action Bar:
  • Mobile:  Stacked buttons
  • Desktop: Inline buttons
```

---

# View Mode Toggle - Interface

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ VIEW MODE CONTROL BAR                                                      │
│ ┌─────────────────────────────────────────────────────────────────────────┐ │
│ │ View: [Cards▪Table]  Show: [15▾] entries      Showing 1 to 15 of 150  │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘

Toggle States:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Active (Cards):
  ┌─────────┐ ┌───────┐
  │ Cards   │ │ Table │
  └─────────┘ └───────┘
  Orange bg   Gray text
  White text  
  Shadow-sm

Active (Table):
  ┌───────┐ ┌─────────┐
  │ Cards │ │ Table   │
  └───────┘ └─────────┘
  Gray text   Orange bg
              White text
              Shadow-sm


CARD VIEW LAYOUT:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

┌─AdminOrderCard────┐
│ [Full Details]    │
│                   │
└───────────────────┘
┌─AdminOrderCard────┐
│ [Full Details]    │
│                   │
└───────────────────┘
┌─AdminOrderCard────┐
│ [Full Details]    │
│                   │
└───────────────────┘

Space-y-4
Stacked vertically


TABLE VIEW LAYOUT:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

┌────────────────────────────────────────────────────────────────────────────┐
│ Order#  │ Customer  │ Items  │ Total   │ Status   │ Payment  │ Actions    │
├────────────────────────────────────────────────────────────────────────────┤
│ ORD-001 │ John Doe  │ 3 prod │ ৳15,000 │ Pending  │ Unpaid   │ ⋮         │
│ ORD-002 │ Jane Doe  │ 2 prod │ ৳8,500  │ Process  │ Paid     │ ⋮         │
└────────────────────────────────────────────────────────────────────────────┘

Horizontal scroll on mobile
Full width on desktop
```

---

# Data Flow Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ LARAVEL BACKEND                                                            │
│ ┌───────────────────────────────────────────────────────────────────────┐  │
│ │ OrderController.php                                                   │  │
│ │ ├─ index() → returns orders with relationships                       │  │
│ │ ├─ statusCounts → calculated statistics                              │  │
│ │ └─ cities → for filtering                                            │  │
│ └───────────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│ INERTIA.JS BRIDGE                                                          │
│ ┌───────────────────────────────────────────────────────────────────────┐  │
│ │ Props:                                                                │  │
│ │ • orders: Paginated collection                                       │  │
│ │ • statusCounts: Statistics array                                     │  │
│ │ • cities: City list                                                  │  │
│ └───────────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
                                    ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│ VUE 3 COMPONENTS                                                           │
│ ┌───────────────────────────────────────────────────────────────────────┐  │
│ │ Index.vue (Main Page)                                                │  │
│ │ ├─ QuickStatsOverview                                                │  │
│ │ │  └─ Computed statistics from orders.data                          │  │
│ │ ├─ OrderFilters                                                      │  │
│ │ │  └─ v-model filters                                               │  │
│ │ ├─ BulkActionsBar                                                    │  │
│ │ │  └─ Selected orders management                                    │  │
│ │ └─ AdminOrderCard (v-for)                                           │  │
│ │    ├─ Priority calculation                                          │  │
│ │    ├─ Risk assessment                                               │  │
│ │    └─ Action handlers                                               │  │
│ └───────────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

**Document Version**: 1.0  
**Last Updated**: 2024  
**Status**: Production Ready ✅
