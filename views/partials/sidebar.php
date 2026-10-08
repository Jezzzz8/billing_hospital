<?php



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);
if (!$currentUser) return;


$sidebarSections = $sidebarSections ?? [];
$currentPage     = $currentPage ?? ($_GET['page'] ?? '');
?>


<div id="sidebarOverlay"
     class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm hidden lg:hidden"
     aria-hidden="true"
     onclick="closeSidebar()"></div>


<aside id="sidebar"
       aria-label="Main navigation"
       class="fixed inset-y-0 left-0 z-50 w-72 sm:w-64 bg-white border-r border-slate-200 flex flex-col
              transform -translate-x-full lg:translate-x-0
              transition-transform duration-300 ease-out
              will-change-transform">

    
    <div class="flex items-center justify-between h-16 px-4 sm:px-5 border-b border-slate-100 shrink-0">
        <div class="flex items-center gap-2.5 min-w-0">
            
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-900 leading-tight truncate">Billing Hospital</p>
                <p class="text-xs text-slate-500 capitalize leading-tight truncate">
                    <?= htmlspecialchars($currentUser['role_name']) ?>
                </p>
            </div>
        </div>

        
        <button type="button" onclick="closeSidebar()"
                aria-label="Close navigation"
                class="lg:hidden -mr-2 p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    
    <nav class="flex-1 overflow-y-auto overscroll-contain px-3 py-4">
        <?php foreach ($sidebarSections as $sectionName => $items): ?>
            <p class="px-3 mt-5 first:mt-0 mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400 select-none">
                <?= htmlspecialchars($sectionName) ?>
            </p>
            <div class="space-y-0.5">
                <?php foreach ($items as $item):
                    $href = $item['href'] ?? '#';
                    $isActive = false;
                    if (str_contains($href, '?page=')) {
                        parse_str(parse_url($href, PHP_URL_QUERY) ?? '', $q);
                        $isActive = ($q['page'] ?? '') === $currentPage;
                    }
                ?>
                    <a href="<?= htmlspecialchars($href) ?>"
                       onclick="closeSidebar()"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                              <?= $isActive
                                  ? 'bg-blue-50 text-blue-700'
                                  : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                        <svg class="w-[18px] h-[18px] shrink-0 <?= $isActive ? 'text-blue-600' : 'text-slate-400' ?>"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/>
                        </svg>
                        <span class="truncate"><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    
    <div class="border-t border-slate-100 p-4 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-sm font-semibold text-slate-700 shrink-0">
                <?= strtoupper(substr($currentUser['first_name'], 0, 1)) ?>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-900 truncate">
                    <?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?>
                </p>
                <p class="text-xs text-slate-500 truncate capitalize">
                    <?= htmlspecialchars($currentUser['role_name']) ?>
                </p>
            </div>
        </div>
        <button id="logoutBtn"
                class="mt-3 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            Sign out
        </button>
    </div>
</aside>


<button type="button" id="sidebarToggle"
        onclick="openSidebar()"
        aria-label="Open navigation"
        class="lg:hidden fixed top-3 left-3 z-30 rounded-lg bg-white text-slate-700 p-2.5  active:scale-95 transition">
    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
</button>

<script>
    (function () {
        const sidebar    = document.getElementById('sidebar');
        const overlay    = document.getElementById('sidebarOverlay');
        const toggleBtn  = document.getElementById('sidebarToggle');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            toggleBtn?.classList.add('hidden');
            
            document.body.style.overflow = 'hidden';
            document.body.style.touchAction = 'none';
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            toggleBtn?.classList.remove('hidden');
            
            document.body.style.overflow = '';
            document.body.style.touchAction = '';
        }

        
        window.openSidebar  = openSidebar;
        window.closeSidebar = closeSidebar;

        
        const mq = window.matchMedia('(min-width: 1024px)');
        mq.addEventListener('change', (e) => {
            if (e.matches) closeSidebar();
        });

        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeSidebar();
        });

        
        sidebar.querySelectorAll('nav a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) closeSidebar();
            });
        });

        
        let touchStartX = 0;
        let touchStartY = 0;

        document.addEventListener('touchstart', (e) => {
            const t = e.touches[0];
            touchStartX = t.clientX;
            touchStartY = t.clientY;
        }, { passive: true });

        document.addEventListener('touchend', (e) => {
            if (window.innerWidth >= 1024) return;
            const t = e.changedTouches[0];
            const dx = t.clientX - touchStartX;
            const dy = Math.abs(t.clientY - touchStartY);

            
            if (touchStartX < 24 && dx > 60 && dy < 40) {
                openSidebar();
            }

            
            if (!sidebar.classList.contains('-translate-x-full') && dx < -60 && dy < 40) {
                closeSidebar();
            }
        }, { passive: true });
    })();
</script>