# 🔔 Notification System Setup Guide

## দুইটা Mode আছে:

### 1️⃣ **Real-time Mode (with Reverb/Pusher)**
নতুন notification এলে **instant** update হবে।

### 2️⃣ **Polling Mode (without real-time)**
প্রতি 60 সেকেন্ডে **automatic refresh** হবে।

---

## 🚀 Setup Instructions

### **Option A: Local Development (Reverb - FREE)**

#### Step 1: .env কনফিগারেশন চেক করুন
```bash
BROADCAST_CONNECTION=reverb
```

#### Step 2: Reverb Server চালান (Terminal 1)
```bash
php artisan reverb:start
```
**⚠️ Important**: এই কমান্ড সবসময় চলতে হবে (background-এ রাখুন)

#### Step 3: Vite Dev Server চালান (Terminal 2)
```bash
npm run dev
```

#### Step 4: Laravel App চালান (Terminal 3)
```bash
php artisan serve
```

**✅ Real-time notifications কাজ করবে!**

---

### **Option B: Production/Pusher (Paid Service)**

#### Step 1: .env এ Pusher credentials যোগ করুন
```bash
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=2112637
PUSHER_APP_KEY=89cd0270b008d7ad5d67
PUSHER_APP_SECRET=0e5b64df2f5d92342b55
PUSHER_APP_CLUSTER=ap2

VITE_BROADCAST_DRIVER=pusher
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

#### Step 2: Cache Clear করুন
```bash
php artisan config:clear
```

#### Step 3: Build করুন
```bash
npm run build
```

**✅ Pusher cloud service দিয়ে real-time কাজ করবে!**

**⚠️ Note**: Pusher free tier credit শেষ হলে notifications কাজ করবে না।

---

### **Option C: No Real-time (FREE Fallback)**

#### যদি Reverb চালাতে না চান বা Pusher credit না থাকে:

#### Step 1: .env এ সেট করুন
```bash
BROADCAST_CONNECTION=log
```

#### Step 2: Cache Clear করুন
```bash
php artisan config:clear
```

**✅ প্রতি 60 সেকেন্ডে automatic refresh হবে (polling mode)**

---

## 🎯 Test করুন

### নতুন Notification তৈরি করুন:
```bash
php artisan tinker
```

Tinker-এ:
```php
$notification = \App\Models\Notification::create([
    'type' => 'order.created',
    'data' => [
        'order_number' => 'ORD-TEST123', 
        'customer_name' => 'Test Customer', 
        'total' => '৳1,000'
    ],
    'is_read' => false
]);

// Real-time broadcast করুন (শুধু Reverb/Pusher mode-এ)
event(new \App\Events\NotificationSent($notification));
```

---

## 📊 Mode Comparison

| Feature | Reverb (Local) | Pusher (Cloud) | Polling (No RT) |
|---------|---------------|----------------|-----------------|
| **Cost** | ✅ FREE | ❌ Paid (credit শেষ হলে বন্ধ) | ✅ FREE |
| **Speed** | ⚡ Instant | ⚡ Instant | 🐢 60s delay |
| **Setup** | 🔧 Medium | ✅ Easy | ✅ Very Easy |
| **Background Process** | ✅ `reverb:start` চালু রাখতে হবে | ❌ Not needed | ❌ Not needed |
| **Production** | ⚠️ Not recommended | ✅ Best | ✅ OK |

---

## ❓ FAQ

### Q: Reverb server বন্ধ করলে কি হবে?
**A**: Automatically polling mode-এ চলে যাবে (60s refresh)

### Q: Pusher credit শেষ হলে?
**A**: Real-time বন্ধ হবে, কিন্তু polling mode চলবে

### Q: Production-এ কোনটা ভালো?
**A**: 
- Budget থাকলে: **Pusher** (best UX)
- Budget না থাকলে: **Polling mode** (still works)

### Q: `php artisan reverb:start` সবসময় চালু রাখতে হবে?
**A**: হ্যাঁ, যদি real-time চান। না চাইলে বন্ধ রাখুন, polling mode চলবে।

---

## 🛠️ Troubleshooting

### Vite build error?
```bash
npm run dev
```
If error persists, check Header.vue syntax.

### Notifications না আসলে?
```bash
# Cache clear করুন
php artisan config:clear
php artisan cache:clear

# .env check করুন
php artisan tinker
>>> config('broadcasting.default')
```

### Real-time কাজ না করলে?
1. Check if Reverb is running: `php artisan reverb:start`
2. Check browser console for Echo errors
3. Fallback: `BROADCAST_CONNECTION=log` সেট করুন

---

**✅ সব setup complete! যেকোনো mode-এ notifications কাজ করবে।**
