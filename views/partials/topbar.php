<?php


?>
<header class="bg-white border-b border-slate-200 h-14 lg:h-16 sticky top-0 z-10">
    <div class="h-full flex items-center justify-between pl-16 pr-4 lg:px-6">

        <h1 class="text-base lg:text-lg font-semibold text-slate-900 truncate">
            <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?>
        </h1>

        <div class="flex items-center gap-3 shrink-0">
            <div class="relative">
                <button id="notificationToggle" type="button" aria-label="Notifications" aria-expanded="false"
                        class="relative rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-50">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17H9.143m10.286 0H4.571c1.143-1.143 1.714-2.857 1.714-5.143V10a5.714 5.714 0 1111.429 0v1.857c0 2.286.571 4 1.714 5.143zM9.714 20h4.572"/>
                    </svg>
                    <span id="notificationCount" class="absolute -right-1 -top-1 hidden min-w-4 rounded-full bg-rose-600 px-1 text-center text-[10px] font-semibold leading-4 text-white"></span>
                </button>
                <section id="notificationPanel" class="absolute right-0 top-11 z-30 hidden w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl" aria-label="Notifications">
                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                        <h2 class="text-sm font-semibold text-slate-900">Notifications</h2>
                        <div class="flex items-center gap-3">
                            <span id="notificationConnection" class="text-xs text-slate-500">Connecting…</span>
                            <button id="markAllNotificationsRead" type="button" aria-label="Mark all notifications as read" title="Mark all read" class="rounded p-1.5 text-blue-700 hover:bg-blue-50 hover:text-blue-800">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6M3 6l2 2m-2 6 2 2"/>
                                </svg>
                            </button>
                            <button id="clearNotifications" type="button" aria-label="Clear all notifications" title="Clear all" class="rounded p-1.5 text-rose-600 hover:bg-rose-50 hover:text-rose-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18m-2 0-.9 14H5.9L5 6m4 0V4h6v2m-5 4v6m4-6v6"/>
                                </svg>
                            </button>
                            <button id="closeNotifications" type="button" aria-label="Close notifications" title="Close" class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div id="notificationList" class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
                        <p class="px-4 py-6 text-center text-sm text-slate-500">No notifications yet.</p>
                    </div>
                </section>
            </div>
            <span class="text-sm text-slate-500 hidden sm:block truncate max-w-[140px]">
                <?= htmlspecialchars($currentUser['first_name']) ?>
            </span>
            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-xs font-semibold text-slate-700">
                <?= strtoupper(substr($currentUser['first_name'], 0, 1)) ?>
            </div>
        </div>
    </div>
</header>