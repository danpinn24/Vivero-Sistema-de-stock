<?php
$actionActual = $_GET['action'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#236b45">
    <title>Vivero Brote Verde</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="index.php?action=dashboard">
            <span class="brand-icon"><i class="bi bi-flower1"></i></span>
            <span><strong>Brote Verde</strong><small>GESTIÓN DE VIVERO</small></span>
        </a>
        <div class="nav-label">MENÚ PRINCIPAL</div>
        <nav class="nav flex-column gap-2">
            <a class="nav-link <?= $actionActual === 'dashboard' ? 'active' : '' ?>" href="index.php?action=dashboard"><i class="bi bi-grid-1x2-fill"></i> Panel principal</a>
            <a class="nav-link <?= $actionActual === 'inventario' ? 'active' : '' ?>" href="index.php?action=inventario"><i class="bi bi-box-seam-fill"></i> Inventario</a>
            <a class="nav-link <?= $actionActual === 'asistente' ? 'active' : '' ?>" href="index.php?action=asistente"><i class="bi bi-chat-square-heart-fill"></i> Asistente IA</a>
        </nav>
        <div class="sidebar-note"><i class="bi bi-leaf-fill"></i><span>Un espacio para hacer crecer tus ideas.</span></div>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <div><span class="topbar-eyebrow">VIVERO · ADMINISTRACIÓN</span><div class="topbar-title">Gestión de inventario</div></div>
            <div class="status-pill"><span></span> Sistema local</div>
        </header>
        <div class="page-content">
