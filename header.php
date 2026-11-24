<?php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/config.php';
}
$current_page = basename($_SERVER['PHP_SELF']);
$logged_member = is_logged_in() ? current_member() : null;
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?><?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        vym: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#0052cc', // Core Royal Brand Blue
                            700: '#0047ba', // Deep Royal Blue
                            800: '#073273',
                            900: '#071e46', // Midnight Navy
                            950: '#041128',
                        },
                        navy: {
                            800: '#0c234a',
                            900: '#071836',
                            950: '#040d1f',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans min-h-screen flex flex-col antialiased selection:bg-vym-600 selection:text-white">

    <!-- Top Navbar -->
    <nav class="bg-navy-950 border-b border-vym-900/60 sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand / Logo -->
                <div class="flex items-center gap-3">
                    <a href="<?= is_logged_in() ? 'dashboard.php' : 'index.php' ?>" class="flex items-center gap-3 group">
                        <div class="w-10 h-10 rounded-xl bg-white p-1 flex items-center justify-center shadow-md shadow-vym-900/30 group-hover:scale-105 transition-transform">
                            <img src="assets/logo.png" alt="PT Visi Yosindo Medikal Logo" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <span class="text-white font-bold text-lg tracking-tight block leading-tight">Visi Academy</span>
                            <span class="text-vym-300 text-[11px] font-semibold tracking-wider uppercase block">PT Visi Yosindo Medikal</span>
                        </div>
                    </a>
                </div>

                <?php if (is_logged_in() && $logged_member): ?>
                    <!-- Desktop Nav Links -->
                    <div class="hidden md:flex items-center space-x-1">
                        <a href="dashboard.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition <?= $current_page === 'dashboard.php' ? 'bg-vym-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' ?>">
                            <i class="fa-solid fa-chart-pie mr-1.5 text-xs"></i> Dashboard
                        </a>
                        <a href="katalog.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition <?= $current_page === 'katalog.php' ? 'bg-vym-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' ?>">
                            <i class="fa-solid fa-kit-medical mr-1.5 text-xs"></i> Katalog Alkes
                        </a>
                        <a href="ajukan-deal.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition <?= $current_page === 'ajukan-deal.php' ? 'bg-vym-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' ?>">
                            <i class="fa-solid fa-circle-plus mr-1.5 text-xs text-vym-400"></i> Ajukan Alkes Baru
                        </a>
                        <a href="tracking-deal.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition <?= $current_page === 'tracking-deal.php' ? 'bg-vym-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' ?>">
                            <i class="fa-solid fa-list-check mr-1.5 text-xs"></i> Tracking Prospek
                        </a>
                        <a href="profil.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition <?= $current_page === 'profil.php' ? 'bg-vym-600 text-white shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' ?>">
                            <i class="fa-solid fa-building-columns mr-1.5 text-xs"></i> Rekening & Profil
                        </a>
                    </div>

                    <!-- Right User Badge & Logout -->
                    <div class="hidden md:flex items-center gap-3">
                        <div class="text-right">
                            <div class="text-xs font-semibold text-white leading-tight">
                                <?= htmlspecialchars((string)($logged_member['nama_member'] ?? '')) ?>
                            </div>
                            <div class="flex items-center justify-end gap-1.5 mt-0.5">
                                <span class="bg-vym-900 text-vym-300 text-[10px] font-bold px-1.5 py-0.5 rounded border border-vym-700/60 font-mono">
                                    <?= htmlspecialchars((string)($logged_member['kode_member'] ?? '')) ?>
                                </span>
                            </div>
                        </div>
                        <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?');" class="text-slate-400 hover:text-rose-400 p-2 rounded-lg hover:bg-slate-800 transition" title="Logout">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </a>
                    </div>

                    <!-- Mobile Hamburger Button -->
                    <div class="md:hidden flex items-center">
                        <button id="mobileMenuBtn" type="button" class="text-slate-300 hover:text-white p-2 rounded-lg hover:bg-slate-800 focus:outline-none">
                            <i class="fa-solid fa-bars text-xl"></i>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="flex items-center gap-2">
                        <a href="login.php" class="text-slate-200 hover:text-white text-sm font-medium px-3.5 py-2 rounded-lg hover:bg-slate-800 transition">
                            Login
                        </a>
                        <a href="register.php" class="bg-vym-600 hover:bg-vym-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-md shadow-vym-600/30 transition">
                            Daftar
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (is_logged_in() && $logged_member): ?>
            <!-- Mobile Menu Dropdown -->
            <div id="mobileMenu" class="hidden md:hidden bg-navy-950 border-t border-slate-800 px-4 pt-3 pb-4 space-y-1">
                <div class="pb-3 border-b border-slate-800 mb-2">
                    <div class="font-semibold text-white text-sm"><?= htmlspecialchars((string)($logged_member['nama_member'] ?? '')) ?></div>
                    <div class="text-xs text-vym-300"><?= htmlspecialchars((string)($logged_member['kode_member'] ?? '')) ?> • <?= htmlspecialchars((string)(($logged_member['institusi_asal'] ?? '') ?: 'Member Visi Academy')) ?></div>
                </div>
                <a href="dashboard.php" class="block px-3 py-2 rounded-md text-sm font-medium <?= $current_page === 'dashboard.php' ? 'bg-vym-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?>">
                    <i class="fa-solid fa-chart-pie mr-2"></i> Dashboard
                </a>
                <a href="katalog.php" class="block px-3 py-2 rounded-md text-sm font-medium <?= $current_page === 'katalog.php' ? 'bg-vym-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?>">
                    <i class="fa-solid fa-kit-medical mr-2"></i> Katalog Alkes
                </a>
                <a href="ajukan-deal.php" class="block px-3 py-2 rounded-md text-sm font-medium <?= $current_page === 'ajukan-deal.php' ? 'bg-vym-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?>">
                    <i class="fa-solid fa-circle-plus mr-2 text-vym-400"></i> Ajukan Alkes Baru
                </a>
                <a href="tracking-deal.php" class="block px-3 py-2 rounded-md text-sm font-medium <?= $current_page === 'tracking-deal.php' ? 'bg-vym-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?>">
                    <i class="fa-solid fa-list-check mr-2"></i> Tracking Prospek
                </a>
                <a href="profil.php" class="block px-3 py-2 rounded-md text-sm font-medium <?= $current_page === 'profil.php' ? 'bg-vym-600 text-white' : 'text-slate-300 hover:bg-slate-800' ?>">
                    <i class="fa-solid fa-building-columns mr-2"></i> Rekening & Profil
                </a>
                <div class="pt-2 border-t border-slate-800">
                    <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?');" class="block px-3 py-2 rounded-md text-sm font-medium text-rose-400 hover:bg-slate-800">
                        <i class="fa-solid fa-arrow-right-from-bracket mr-2"></i> Logout
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </nav>

    <!-- Flash Message Banner -->
    <?php if ($flash): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <?php 
                $bg_color = 'bg-vym-50 border-vym-600 text-vym-900';
                $icon = 'fa-circle-check text-vym-600';
                if ($flash['type'] === 'error') {
                    $bg_color = 'bg-rose-50 border-rose-500 text-rose-900';
                    $icon = 'fa-circle-exclamation text-rose-600';
                } elseif ($flash['type'] === 'warning') {
                    $bg_color = 'bg-amber-50 border-amber-500 text-amber-900';
                    $icon = 'fa-triangle-exclamation text-amber-600';
                } elseif ($flash['type'] === 'info') {
                    $bg_color = 'bg-sky-50 border-sky-500 text-sky-900';
                    $icon = 'fa-circle-info text-sky-600';
                }
            ?>
            <div class="flex items-center p-4 rounded-xl border-l-4 <?= $bg_color ?> shadow-sm animate-fade-in">
                <i class="fa-solid <?= $icon ?> text-xl mr-3"></i>
                <div class="text-sm font-medium flex-1">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
                <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
