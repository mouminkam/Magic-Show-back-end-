# رفع Magic-Show Back-End على GitHub

## 1) إنشاء المستودع على GitHub

1. ادخل إلى [github.com/new](https://github.com/new).
2. اختر اسم المستودع (مثلاً `magic-show-backend`).
3. اختر **Private** أو **Public** حسب رغبتك.
4. **لا** تفعّل "Add a README" إذا كان المشروع محلياً جاهزاً (لتجنب تعارض أول دفع).
5. أنشئ المستودع.

## 2) ربط المشروع المحلي (من مجلد `Magic-Show Back-End Server`)

```powershell
cd "E:\Peak Link Project\magic show\Magic Show Project(NEXT.JS) - Copy (2) - Copy\Magic-Show Back-End Server"
git remote add origin https://github.com/YOUR_USER/YOUR_REPO.git
git push -u origin main
```

- استبدل `YOUR_USER` و`YOUR_REPO` بقيمك.
- إن طُلب منك تسجيل الدخول، استخدم **Personal Access Token** (GitHub → Settings → Developer settings) ككلمة مرور عند استخدام HTTPS.

## 3) إن كان `origin` موجوداً مسبقاً

```powershell
git remote -v
git remote set-url origin https://github.com/YOUR_USER/YOUR_REPO.git
git push -u origin main
```

## ما لا يُرفع (مهم)

- `.env` — أسرار محلية؛ يُنسخ من `.env.example` على كل بيئة.
- `vendor/` — يُعاد بناؤه بـ `composer install`.
- `vendor.zip` — نسخة احتياطية محلية؛ مُستثناة في `.gitignore`.
- كاش Blade المُجمَّع تحت `storage/framework/views/*.php` — مُستثنى؛ يُعاد توليده تلقائياً.

## بعد الاستنساخ على سيرفر أو جهاز جديد

```bash
composer install --no-dev --optimize-autoloader
copy .env.example .env   # أو cp على Linux/Mac
php artisan key:generate
php artisan migrate --force
php artisan storage:link
```

راجع [README.md](../README.md) في جذر المشروع للتفاصيل.
