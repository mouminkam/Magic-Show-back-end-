# إعداد Firebase للتنبيهات الفورية (طلبات جديدة)

## لماذا Firebase؟
- **Real-time**: الطلب يصل للداشبورد فوراً بدون انتظار 12 ثانية (Polling).
- **مودال + نغمة** تظهر مباشرة عند إنشاء أي طلب من الموقع.

## التقنية المستخدمة
- **Firestore**: قاعدة بيانات فورية؛ اللارافيل يكتب "إشعار طلب جديد" عند إنشاء الطلب، والداشبورد يسمع (listener) فوراً ويظهر المودال مع النغمة.
- **ليس Socket.io ولا WebSocket على السيرفر** — كل شيء عبر Firebase.

---

## الخطوات اللي لازم تعملها أنت

### 1) إنشاء مشروع Firebase
1. ادخل على [Firebase Console](https://console.firebase.google.com/)
2. اضغط **Add project** (أو استخدم مشروع موجود)
3. سمّ المشروع (مثلاً `magic-shoe-admin`)
4. اختياري: عطّل Google Analytics إذا ما بدك إياه → **Create project**

### 2) تفعيل Firestore
1. من القائمة اليسرى: **Build** → **Firestore Database**
2. اضغط **Create database**
3. اختر **Start in test mode** (للتجربة) أو **Production** وحدد القواعد لاحقاً
4. اختر منطقة (Region) قريبة منك → **Enable**

### 3) الحصول على بيانات ويب (للداشبورد)
1. في المشروع: **Project settings** (أيقونة الترس)
2. تحت **Your apps** اضغط أيقونة **</>** (Web)
3. سجّل اسم التطبيق (مثلاً `Magic Shoe Admin`) → **Register app**
4. انسخ كائن `firebaseConfig` (يحتوي `apiKey`, `authDomain`, `projectId`, `storageBucket`, `messagingSenderId`, `appId`)
5. ضع هذه القيم في ملف `.env` في مشروع اللارافيل (انظر تحت "إعداد .env")

### 4) الحصول على Service Account (للسيرفر Laravel)
1. في **Project settings** → تبويب **Service accounts**
2. اضغط **Generate new private key** → **Generate key**
3. سيُحمّل ملف JSON — **احفظه بأمان** ولا ترفعه على Git
4. ضع الملف في المشروع مثلاً:  
   `storage/app/firebase-credentials.json`  
   أو انسخ محتواه كسطر واحد في `.env` (انظر تحت)

### 5) قواعد Firestore (الأمان)
1. **Firestore** → تبويب **Rules**
2. استخدم مثلاً (للقراءة للجميع والكتابة من السيرفر فقط عبر Admin SDK):

```
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    match /admin_new_orders/{docId} {
      allow read: if true;
      allow write: if false;
    }
  }
}
```

- القراءة `read` من الداشبورد (عميل الويب) مسموحة.
- الكتابة `write` فقط من Laravel عبر **Admin SDK** (مفتاح الخدمة)، لذلك `allow write: if false` آمن للعميل.

### 6) إعداد .env في Laravel

أضف في `.env` (استبدل القيم من خطوتك 3 و 4):

```env
# Firebase - ويب (للداشبورد - إشعارات فورية)
FIREBASE_API_KEY=AIza...
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_STORAGE_BUCKET=your-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=123456789
FIREBASE_APP_ID=1:123456789:web:abc123

# Firebase - سيرفر (كتابة إشعار الطلب الجديد إلى Firestore)
# الطريقة 1: مسار ملف Service Account JSON (نسبي من مجلد المشروع)
FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
# الطريقة 2: أو ضع محتوى الـ JSON كسطر واحد في FIREBASE_CREDENTIALS_JSON
# FIREBASE_CREDENTIALS_JSON={"type":"service_account","project_id":"..."...}
```

- ضع ملف الـ Service Account في: `storage/app/firebase-credentials.json` (أنشئ المجلد إن لزم).
- بعد التعديل شغّل: `php artisan config:clear`

---

## ماذا يفعل الكود بعد الإعداد؟

1. **عند إنشاء طلب** (من الموقع أو من الأدمن): Laravel يكتب مستنداً في مجموعة Firestore اسمها `admin_new_orders` (رقم الطلب، الرابط، إلخ).
2. **صفحة الداشبورد**: مفتوحة وتستمع (listener) لنفس المجموعة؛ فور ظهور مستند جديد تفتح المودال وتشغّل النغمة.

إذا نفذت الخطوات أعلاه وأضفت القيم في `.env`، النظام يعمل real-time بعد تشغيل السيرفر وتحديث الصفحة.
