{{--
  Server fault page. DELIBERATELY self-contained: no <x-layout>, no t(), no route().

  The layout composer reads menus out of the cache/database and t() reads the
  translations table — so if the database or cache is what just failed, rendering the
  normal chrome would throw a second exception and Laravel would fall back to its bare
  English page, which is exactly what this view exists to avoid. Everything here is
  inline, which means the text cannot be localised from the database; Azerbaijani is the
  source language, so that is what it carries.

  Keep this file dependency-free. Adding a component or a translation call to it will
  quietly disable it in the one situation it is for.
--}}
<!DOCTYPE html>
<html lang="az">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Server xətası — ARCHİ</title>
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
    .code { font-size: 96px; line-height: 1; font-weight: 700; color: rgba(0,0,0,.9); margin: 0; }
    .rule { display: block; width: 56px; height: 4px; border-radius: 2px; background: #fdfe00; margin: 18px auto; }
    h1 { font-size: 34px; font-weight: 700; margin: 0 0 18px; }
    p { max-width: 480px; margin: 0 auto 26px; font-size: 16px; line-height: 1.5; color: rgba(0,0,0,.5); }
    a {
      display: inline-block; height: 50px; line-height: 50px; padding: 0 26px;
      border-radius: 4px; font-size: 15px; font-weight: 600; text-decoration: none;
      background: #fdfe00; color: #1a1a1a;
    }
    @media (max-width: 560px) { .code { font-size: 64px; } h1 { font-size: 26px; } }
  </style>
</head>
<body>
  <div>
    <p class="code">500</p>
    <span class="rule"></span>
    <h1>Sistemdə xəta baş verdi</h1>
    <p>Texniki problem yarandı və səhifəni göstərə bilmirik. Komandamız məlumatlandırılıb — bir neçə dəqiqə sonra yenidən cəhd edin.</p>
    <a href="/">Ana səhifəyə qayıt</a>
  </div>
</body>
</html>
