<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>الملف كبير جداً — منصة المعرفة السعودية</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: #f7f8f6; color: #1f2a37; padding: 24px;
            font-family: 'Tajawal', system-ui, -apple-system, 'Segoe UI', sans-serif;
        }
        .card {
            width: 100%; max-width: 30rem; background: #fff; border: 1px solid #e5e7eb;
            border-radius: 20px; padding: 40px 32px; text-align: center;
            box-shadow: 0 10px 30px rgba(16,24,40,.05);
        }
        .badge {
            width: 56px; height: 56px; margin: 0 auto 20px; border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            background: #fef2f2; color: #dc2626; font-size: 28px; font-weight: 700;
        }
        h1 { font-size: 22px; margin: 0 0 10px; color: #111827; }
        p { font-size: 15px; line-height: 1.9; color: #6b7280; margin: 0 0 24px; }
        .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        a.btn {
            display: inline-flex; align-items: center; text-decoration: none; font-weight: 600; font-size: 14px;
            padding: 11px 20px; border-radius: 12px;
        }
        .btn-primary { background: #1b6b46; color: #fff; }
        .btn-secondary { background: #f3f4f6; color: #374151; }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">!</div>
        <h1>حجم الصورة كبير جداً</h1>
        <p>
            الصورة التي حاولت رفعها تتجاوز الحد المسموح.
            يُرجى اختيار صورة بحجم <strong>5 ميجابايت أو أقل</strong> ثم المحاولة مرة أخرى.
        </p>
        <div class="actions">
            <a class="btn btn-primary" href="{{ route('posts.create') }}">إنشاء مقال جديد</a>
            <a class="btn btn-secondary" href="{{ route('dashboard') }}">الصفحة الشخصية</a>
        </div>
    </div>
</body>
</html>
