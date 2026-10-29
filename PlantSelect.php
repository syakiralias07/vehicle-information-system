<?php
require_once __DIR__.'/plants.php';
$visPlantChooser=true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Select Plant | Vehicle Information System</title>
<?php require __DIR__.'/theme.php'; ?>
<style>
body{margin:0!important;background:#f3f6fa!important;background-image:none!important;padding:0!important;font-family:"Segoe UI",Arial,sans-serif;color:#172d43}
.plant-topbar{background:#fff;border-bottom:1px solid #e1e7ef}
.plant-nav{max-width:1380px;min-height:86px;margin:auto;padding:16px 40px;display:flex;align-items:center;justify-content:space-between;gap:24px}
.plant-brand{display:flex;align-items:center;gap:20px;text-decoration:none;color:#172d43;min-width:0}
.plant-logo{width:88px;height:46px;object-fit:contain}
.plant-brand-copy{border-left:1px solid #dce3eb;padding-left:20px;display:flex;flex-direction:column;gap:5px}
.plant-brand-copy strong{font-size:16px;letter-spacing:-.3px}.plant-brand-copy span{color:#7b8999;font-size:10px;font-weight:700;letter-spacing:1.9px;text-transform:uppercase}
.plant-admin{display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:11px 16px;border:1px solid #dae2ec;border-radius:9px;color:#264761;background:#fff;text-decoration:none;font-size:13px;font-weight:600;white-space:nowrap;transition:background .2s,border-color .2s}.plant-admin:hover{background:#edf4fb;border-color:#90b1cc}.plant-admin svg{width:15px;height:15px}
.plant-page{max-width:1380px;margin:auto;padding:38px 40px 24px}
.plant-intro{display:flex;align-items:flex-end;justify-content:space-between;gap:26px;margin-bottom:28px}
.plant-eyebrow{margin:0 0 12px;display:flex;align-items:center;gap:8px;font-size:10px;letter-spacing:2px;font-weight:750;color:#55738c;text-transform:uppercase}.plant-eyebrow::before{content:"";width:20px;height:2px;background:#1c82b5}
.plant-intro h1{font-size:clamp(30px,3.3vw,43px);font-weight:750;line-height:1.15;letter-spacing:-1.6px;margin:0;color:#102d47}
.plant-intro>p{max-width:325px;color:#62758a;font-size:14px;line-height:1.7;margin:0 0 3px}
.plant-layout{display:grid;grid-template-columns:minmax(260px,.8fr) minmax(0,1.7fr);gap:28px;align-items:stretch}
.plant-feature{position:relative;isolation:isolate;overflow:hidden;min-height:510px;background:#102f48;border-radius:18px;padding:32px;display:flex;flex-direction:column;justify-content:space-between;color:#fff}
.plant-feature-photo{position:absolute;inset:0;z-index:-2;width:100%;height:100%;object-fit:cover;object-position:43% center;filter:saturate(.5)}
.plant-feature::before{content:"";position:absolute;inset:0;z-index:-1;background:linear-gradient(180deg,rgba(9,36,58,.95) 0%,rgba(9,36,58,.88) 35%,rgba(9,36,58,.2) 68%,rgba(9,36,58,.94) 100%)}
.plant-feature-kicker{display:inline-flex;gap:8px;align-items:center;font-size:10px;letter-spacing:1.8px;color:#c2d9e8;font-weight:600;text-transform:uppercase}.plant-feature-kicker svg{width:17px;height:17px;color:#65c4dd}
.plant-feature h2{font-size:clamp(30px,3vw,39px);letter-spacing:-1.1px;line-height:1.18;font-weight:650;margin:24px 0 16px;color:white}
.plant-feature-copy{font-size:13px;line-height:1.85;max-width:255px;margin:0;color:#bdd1e0}
.plant-feature-bottom{border-top:1px solid #ffffff38;padding-top:20px;display:flex;align-items:center;gap:15px}.plant-feature-number{font-size:42px;line-height:1;letter-spacing:-2px;font-weight:600}.plant-feature-bottom p{font-size:12px;line-height:1.6;margin:0;color:#c5d9e5}.plant-feature-bottom strong{color:#fff;font-weight:600;display:block}
.plant-selection{min-width:0;display:flex;flex-direction:column}
.plant-selection-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:1px 0 15px}.plant-selection-heading h2{font-size:17px;font-weight:700;letter-spacing:-.3px;margin:0;color:#183a55}.plant-selection-heading span{font-size:11px;color:#667b8f;display:flex;align-items:center;gap:7px}.plant-selection-heading span::before{content:"";width:5px;height:5px;background:#98aabb;border-radius:50%}
.plant-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;flex:1}
.plant-card{position:relative;display:flex;align-items:center;gap:17px;padding:23px 22px;border:1px solid #e0e6ee;border-radius:13px;background:#fff;color:#163b57;text-decoration:none;box-shadow:0 2px 3px #17364b03;transition:transform .2s,box-shadow .2s,border-color .2s,background .2s;overflow:hidden;min-width:0}
.plant-card:last-child{grid-column:1/-1}
.plant-card:hover,.plant-card:focus-visible{transform:translateY(-2px);border-color:#5a9ec1;box-shadow:0 9px 24px #163a5512;background:#fcfeff}.plant-card:active{transform:translateY(0)}
.plant-icon{width:45px;height:48px;display:grid;place-items:center;flex-shrink:0;border:1px solid #e6edf4;background:#f5f8fb;border-radius:10px;color:#557a96;transition:background .2s,color .2s}.plant-icon svg{width:24px;height:24px;stroke-width:1.35}.plant-card:hover .plant-icon{background:#e6f2f9;color:#1774a3;border-color:#d6e9f4}
.plant-card-copy{min-width:0}.plant-card h3{font-size:17px;letter-spacing:-.35px;margin:0 0 7px;font-weight:700;line-height:1.3}.plant-card p{margin:0;color:#62758a;font-size:11px;line-height:1.5}
.plant-arrow{margin-left:auto;flex-shrink:0;display:grid;place-items:center;width:28px;height:28px;border-radius:50%;color:#8396a5;transition:color .2s,background .2s,transform .2s}.plant-arrow svg{width:17px;height:17px}.plant-card:hover .plant-arrow{background:#1774a3;color:#fff;transform:translateX(2px)}
.plant-selection-note{display:flex;gap:8px;align-items:center;margin:15px 0 0;font-size:11px;color:#667b8f;line-height:1.6}.plant-selection-note svg{width:14px;height:14px;flex-shrink:0;color:#668499}
.plant-footer{margin-top:26px;padding-top:18px;border-top:1px solid #e0e6ed;display:flex;justify-content:space-between;gap:14px;color:#8a98a6;font-size:10px;letter-spacing:.2px}.plant-footer strong{font-weight:600;color:#687d8f}
@media(min-width:1500px){.plant-page{padding-top:48px}.plant-intro{margin-bottom:34px}.plant-feature{min-height:540px}}
@media(max-width:1000px){.plant-nav{padding:16px 25px}.plant-page{padding:30px 25px 22px}.plant-layout{grid-template-columns:minmax(235px,.8fr) minmax(0,1.4fr);gap:20px}.plant-feature{padding:25px}.plant-card{padding:20px 15px;gap:12px}.plant-icon{width:38px;height:42px}.plant-card h3{font-size:15px}.plant-intro>p{max-width:265px;font-size:13px}}
@media(max-width:760px){.plant-intro{display:block}.plant-intro>p{max-width:460px;margin-top:14px}.plant-layout{grid-template-columns:1fr}.plant-feature{min-height:240px;padding:26px;justify-content:flex-start}.plant-feature h2{margin:16px 0 10px;font-size:31px}.plant-feature h2 br{display:initial}.plant-feature-copy{max-width:330px}.plant-feature-photo{object-position:center 48%}.plant-feature::before{background:linear-gradient(90deg,rgba(9,36,58,.97),rgba(9,36,58,.75) 55%,rgba(9,36,58,.25))}.plant-feature-bottom{position:absolute;bottom:24px;right:24px;border:0;padding:0}.plant-feature-number{font-size:35px}.plant-feature-bottom p{display:none}.plant-selection-heading{margin-top:3px}.plant-grid{gap:12px}.plant-card{min-height:105px;padding:20px}.plant-icon{width:43px;height:45px}.plant-card h3{font-size:17px}.plant-brand-copy strong{font-size:14px}.plant-logo{width:70px}.plant-brand-copy{padding-left:15px}.plant-brand{gap:15px}.plant-footer{margin-top:24px}}
@media(max-width:480px){.plant-nav{min-height:74px;padding:13px 18px;gap:12px}.plant-logo{width:56px;height:36px}.plant-brand{gap:11px}.plant-brand-copy{padding-left:11px}.plant-brand-copy strong{font-size:12px;max-width:130px;line-height:1.4}.plant-brand-copy span{font-size:8px;letter-spacing:1px}.plant-admin{padding:10px;font-size:11px;gap:5px}.plant-admin svg{width:13px}.plant-page{padding:26px 18px 20px}.plant-intro h1{font-size:32px;letter-spacing:-1px}.plant-eyebrow{font-size:9px;letter-spacing:1.5px}.plant-intro{margin-bottom:22px}.plant-layout{gap:24px}.plant-feature{min-height:225px;padding:23px;border-radius:14px}.plant-feature h2{max-width:250px;font-size:29px}.plant-feature h2 br{display:initial}.plant-feature-copy{max-width:235px;font-size:12px}.plant-grid{grid-template-columns:1fr;gap:10px}.plant-card{min-height:91px;padding:18px}.plant-card:last-child{grid-column:auto}.plant-card h3{font-size:17px;margin-bottom:4px}.plant-card p{font-size:11px}.plant-arrow{width:30px;height:30px;background:#f4f7fa}.plant-selection-note{align-items:flex-start}.plant-footer{font-size:9px;line-height:1.6}.plant-footer>span:last-child{text-align:right}}
@media(prefers-reduced-motion:reduce){.plant-card,.plant-arrow,.plant-icon{transition:none}}
</style>
</head>
<body>
<header class="plant-topbar">
<nav class="plant-nav" aria-label="Main navigation">
<div class="plant-brand"><img class="plant-logo" src="assets/PHN%20transparent%20logo.png" alt="PHN"><span class="plant-brand-copy"><strong>Vehicle Information System</strong><span>Assembly database</span></span></div>
<a class="plant-admin" href="admin.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/></svg>Admin Login</a>
</nav>
</header>
<main class="plant-page">
<header class="plant-intro"><div><p class="plant-eyebrow">PHN Industry · Production knowledge</p><h1>Vehicle information,<br>for every plant.</h1></div><p>Your models, assemblies and parts.<br>Select a plant to find the information you need.</p></header>
<div class="plant-layout">
<aside class="plant-feature" aria-label="PHN production network">
<img class="plant-feature-photo" src="assets/PHN%20building%20photo.jpg" alt="" fetchpriority="high">
<div><span class="plant-feature-kicker"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M3 21V10l6 3V8l6 3V3h4l2 18H3Z"/><path d="M7 17h2m3 0h2m3 0h1"/></svg>Our production network</span><h2>Different plants.<br>One connected<br>knowledge hub.</h2><p class="plant-feature-copy">Explore the vehicle catalogue and assembly information for your production plant.</p></div>
<div class="plant-feature-bottom"><span class="plant-feature-number">07</span><p><strong>Production plants</strong>Choose yours to get started</p></div>
</aside>
<section class="plant-selection" aria-labelledby="plant-heading">
<div class="plant-selection-heading"><h2 id="plant-heading">Select Plant</h2><span>7 locations</span></div>
<div class="plant-grid">
<?php foreach(visPlants() as $slug=>$label): ?>
<a class="plant-card" href="index.php?plant=<?=htmlspecialchars($slug,ENT_QUOTES)?>" aria-label="Open <?=htmlspecialchars($label,ENT_QUOTES)?> vehicle catalogue">
<span class="plant-icon" aria-hidden="true"><svg viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-linecap="round"><path d="M4 24V12l7 4V10l7 4V5h5l1 19H4Z"/><path d="M8 20h2m4 0h2m4 0h1M19 8h4"/></svg></span>
<div class="plant-card-copy"><h3><?=htmlspecialchars($label,ENT_QUOTES)?></h3><p>Models &amp; assemblies</p></div>
<span class="plant-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-5-5 5 5-5 5"/></svg></span>
</a>
<?php endforeach; ?>
</div>
<p class="plant-selection-note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10v1"/></svg>Each plant has its own vehicle catalogue. You can change plants at any time.</p>
</section>
</div>
<footer class="plant-footer"><span><strong>PHN INDUSTRY</strong> &nbsp; / &nbsp; Vehicle Information System</span><span>Plant &rarr; Model &rarr; Assembly information</span></footer>
</main>
</body>
</html>
