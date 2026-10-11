<?php $pageTitle = $pageTitle ?? 'ابزار'; ?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#2563EB">
<link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png">
<link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
<link rel="stylesheet" href="assets/theme.css">
<style>
  body{ display:flex; align-items:center; justify-content:center; min-height:100vh; }
  .auth-box{
    width:100%; max-width:370px; background:var(--ink-2); border:1px solid var(--line);
    border-radius:var(--radius); box-shadow:var(--shadow); padding:28px;
  }
  .auth-logo{
    width:52px; height:52px; border-radius:14px; display:grid; place-items:center; margin:0 auto 14px;
    overflow:hidden;
    box-shadow:0 6px 18px rgba(37,99,235,.35);
  }
  .auth-box h1{ font-size:1.2rem; font-weight:800; margin-bottom:6px; text-align:center; }
  .auth-box .hint{ color:var(--text-dim); font-size:.88rem; margin-bottom:18px; text-align:center; }
  .auth-box form{ display:flex; flex-direction:column; gap:12px; }
  .auth-box input{
    background:var(--ink); color:var(--text); border:1px solid var(--line);
    border-radius:var(--radius-sm); padding:11px 12px; font-family:inherit; font-size:.95rem;
  }
  .auth-box input:focus{ outline:none; border-color:var(--gold); background:var(--ink-2); box-shadow:0 0 0 4px rgba(37,99,235,.12); }
  .auth-box .btn{ justify-content:center; }
  .auth-box .err{
    background:rgba(229,83,75,.12); color:var(--danger); border:1px solid rgba(229,83,75,.3);
    border-radius:var(--radius-sm); padding:9px 12px; font-size:.86rem; margin-bottom:14px;
  }
  .captcha-row{ display:flex; flex-direction:column; gap:8px; }
  .captcha-row img{ width:100%; border-radius:var(--radius-sm); border:1px solid var(--line); cursor:pointer; }
  .auth-box a{ color:var(--gold-ink); text-decoration:none; font-size:.85rem; }
  .auth-box a:hover{ text-decoration:underline; }
</style>
</head>
<body>
