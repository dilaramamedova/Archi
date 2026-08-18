{{--
  Maintenance mode (`php artisan down`), shown during a deploy. Self-contained for the
  same reason as 500: during a deploy the caches are being rebuilt and the database may be
  mid-migration, so this view must not read either. See the note in 500.blade.php before
  adding anything to it.
--}}
<!DOCTYPE html>
<html lang="az">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Texniki xidmət — ARCHİ</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f5f7f9;
      color: #1a1a1a;
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
      text-align: center;
      padding: 28px;
    }
    .rule { display: block; width: 56px; height: 4px; border-radius: 2px; background: #fdfe00; margin: 18px auto; }
    h1 { font-size: 34px; font-weight: 700; margin: 0 0 18px; }
    p { max-width: 480px; margin: 0 auto; font-size: 16px; line-height: 1.5; color: rgba(0,0,0,.5); }
    .brand { font-size: 22px; font-weight: 700; letter-spacing: .04em; margin: 0; }
    @media (max-width: 560px) { h1 { font-size: 26px; } }
  </style>
</head>
<body>
  <div>
    <p class="brand">ARCHİ</p>
    <span class="rule"></span>
    <h1>Texniki xidmət aparılır</h1>
    <p>Sayt qısa müddətlik yenilənir. Bir neçə dəqiqə sonra yenidən açın — məlumatlarınız təhlükəsizdir.</p>
  </div>
</body>
</html>
