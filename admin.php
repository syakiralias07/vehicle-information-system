<?php
// Administrative workspace. Authentication and catalogue operations remain in admin-api.php.
declare(strict_types=1);
require_once __DIR__.'/plant-page.php';
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Administration | Vehicle Information System</title>
<link rel="stylesheet" href="admin.css?v=<?=filemtime(__DIR__.'/admin.css')?>">
</head>
<body>
<header class="admin-topbar">
<div class="admin-brand"><img src="assets/PHN%20transparent%20logo.png" alt="PHN"><span><strong>Vehicle Information System</strong><small>Administration workspace</small></span></div>
<a class="public-link" id="publicWebsite" href="index.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M14 4h6v6m-1-5-9 9"/><path d="M10 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-5"/></svg>Public website</a>
</header>
<main class="admin-shell">
<section id="loginBox" class="login-panel panel">
<span class="eyebrow">PHN Industry</span><h1>Welcome back.</h1><p class="section-description">Sign in to manage your plant catalogues.</p>
<form id="login"><label for="username">Admin ID</label><input id="username" autocomplete="username" placeholder="Enter your admin ID" required><label for="password">Password</label><input type="password" id="password" autocomplete="current-password" placeholder="Enter your password" required><button class="login-submit" type="submit">Sign in <span aria-hidden="true">&rarr;</span></button></form><p id="loginError" class="error" role="alert"></p>
</section>
<fieldset id="workspace" hidden>
<div class="workspace-layout">
<aside class="admin-sidebar" aria-label="Workspace navigation">
<div class="sidebar-heading"><span class="sidebar-mark" aria-hidden="true"><svg viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M4 24V12l7 4V10l7 4V5h5l1 19H4Z"/><path d="M8 20h2m4 0h2m4 0h1"/></svg></span><div><strong>Catalogue studio</strong><small>Administration</small></div></div>
<p class="sidebar-label">WORKSPACE</p>
<nav class="workspace-nav"><a href="#vehicle-records"><span aria-hidden="true">01</span>Vehicle records<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></a><a href="#image-management"><span aria-hidden="true">02</span>Image management<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></a><a href="#image-audit"><span aria-hidden="true">03</span>Image audit<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></a></nav>
<div class="sidebar-note"><span class="sidebar-dot"></span><strong>One workspace.<br>Seven plants.</strong><p>Choose a plant to manage its vehicle information and pictures.</p></div>
<a class="sidebar-home" href="index.php">&larr; Back to plant selection</a>
</aside>
<div class="workspace-content">
<header class="workspace-heading"><div><span class="eyebrow">Catalogue administration</span><h1>Your production knowledge.<br>Everything in place.</h1><p>Manage vehicle records, pictures and published information.</p></div><span class="workspace-badge">Admin workspace</span></header>
<section class="plant-controls panel" aria-label="Plant and publishing controls">
<div class="plant-control"><label for="plant">Editing plant</label><select id="plant"><?php foreach(visPlants() as $slug=>$label): ?><option value="<?=htmlspecialchars($slug,ENT_QUOTES)?>" <?=$slug==='fif'?'selected':''?>><?=htmlspecialchars($label,ENT_QUOTES)?></option><?php endforeach; ?></select></div>
<span id="welcome" class="plant-context">FiF</span>
<div class="control-actions"><button class="ghost" id="refresh" type="button"><span aria-hidden="true">&#8635;</span> Refresh</button><button id="publish" type="button">Publish update date</button><button class="text-button" id="logout" type="button">Log out <span aria-hidden="true">&rarr;</span></button></div>
<div id="message" role="status" aria-live="polite"></div>
</section>
<div class="workspace-stats" aria-label="Selected plant overview"><div class="stat"><span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M4 10h16M10 10v10"/></svg></span><div><strong id="statTables">&mdash;</strong><span>Data tables</span></div></div><div class="stat"><span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 3h8l4 4v14H5V3h2Z"/><path d="M14 3v5h5M8 12h8m-8 4h6"/></svg></span><div><strong id="statRecords">&mdash;</strong><span>Total records</span></div></div><div class="stat"><span class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="8" cy="9" r="1.5"/><path d="m4 18 5-5 4 4 4-6 4 6"/></svg></span><div><strong id="statAssets">&mdash;</strong><span>Available images</span></div></div></div>
<section class="panel records-panel" id="vehicle-records" aria-labelledby="records-title">
<div class="section-heading"><span class="section-number">01</span><div><h2 id="records-title">Vehicle records</h2><p>Manage models, variants, grades and assembly information for this plant.</p></div></div>
<div class="record-toolbar"><div><label for="table">Data table</label><select id="table"></select></div><div class="filter-control"><label for="filter">Find a record</label><input id="filter" placeholder="Search model, variant or part..."></div><button id="newRecord" type="button"><span aria-hidden="true">+</span> New record</button></div>
<div class="record-workbench"><div class="record-browser"><div class="browser-heading"><strong>Records</strong><span id="listCount">Select a table</span></div><div id="recordList" aria-label="Database records"></div></div><div class="record-editor"><div class="editor-heading"><span class="eyebrow">Record details</span><h3 id="editTitle">Select a record</h3></div><div id="fields" class="fields"></div><div class="editor-actions"><button id="saveRecord" type="button">Save record</button><button id="deleteRecord" class="danger" type="button">Delete record</button></div><p class="editor-note">Changes apply to the selected plant.</p></div></div>
</section>
<section class="panel" id="image-management" aria-labelledby="images-title">
<div class="section-heading"><span class="section-number">02</span><div><h2 id="images-title">Image management</h2><p>Select a vehicle record above, then upload or assign its picture.</p></div></div>
<form id="uploadForm" class="upload-area"><div><label for="uploadFile">Add a picture</label><p>JPEG, PNG, WebP or GIF &middot; up to 12 MB</p></div><input type="file" id="uploadFile" accept="image/png,image/jpeg,image/webp,image/gif" required><button type="submit">Upload image <span aria-hidden="true">&uarr;</span></button></form>
<div class="mapping-fields"><div><label for="kind">Image type</label><select id="kind"><option value="part">Variant difference / Part</option><option value="overview">Assembly overview</option><option value="opl">OPL</option><option value="tagging">Tagging</option><option value="grade_card">Grade / market card</option><option value="">Other</option></select></div><div><label for="pictureName">Picture name</label><input id="pictureName" placeholder="Exact record picture name"></div><div><label for="grade">Grade override <small>(optional)</small></label><input id="grade" placeholder="Vietnam, Executive..."></div><div><label for="part">Part number override <small>(optional)</small></label><input id="part" placeholder="Part number"></div></div>
<div class="asset-assignment"><label for="asset">Assigned image file</label><select id="asset"><option value="">Use automatic mapping (no override)</option></select><div class="image-preview"><img id="preview" hidden alt="Selected image preview"></div></div>
<div class="image-actions"><button id="saveMapping" type="button">Save image assignment</button><button class="danger" id="deleteImage" type="button">Delete uploaded image</button></div><p class="muted">To remove an assignment, select automatic mapping and save.</p>
</section>
<section class="panel audit-panel" id="image-audit" aria-labelledby="audit-title"><div class="audit-intro"><div class="section-heading"><span class="section-number">03</span><div><h2 id="audit-title">Image audit</h2><p>Check that the selected plant's record images are available.</p></div></div><button id="auditButton" class="ghost" type="button">Run image audit <span aria-hidden="true">&rarr;</span></button></div><div id="auditResult" role="status" aria-live="polite"></div></section>
<footer class="workspace-footer"><strong>PHN INDUSTRY</strong><span>Vehicle Information System &middot; Administration</span></footer>
</div>
</div>
</fieldset>
</main>
<script src="admin.js?v=<?=filemtime(__DIR__.'/admin.js')?>"></script>
</body>
</html>
