<?php


?>
<header class="bg-white border-b border-slate-200 h-14 lg:h-16 sticky top-0 z-10">
    <div class="h-full flex items-center justify-between pl-16 pr-4 lg:px-6">

        <h1 class="text-base lg:text-lg font-semibold text-slate-900 truncate">
            <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?>
        </h1>

        <div class="flex items-center gap-3 shrink-0">
            <span class="text-sm text-slate-500 hidden sm:block truncate max-w-[140px]">
                <?= htmlspecialchars($currentUser['first_name']) ?>
            </span>
            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-xs font-semibold text-slate-700">
                <?= strtoupper(substr($currentUser['first_name'], 0, 1)) ?>
            </div>
        </div>
    </div>
</header>