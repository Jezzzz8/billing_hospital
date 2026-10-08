<?php

$pageTitle  = 'Sign in';
$pageScript = '/billing_hospital/assets/js/login.js';
require __DIR__ . '/partials/header.php';
?>



<div class="min-h-screen grid grid-cols-1 lg:grid-cols-8">

    
    
    
    <div class="hidden lg:flex lg:col-span-5 relative bg-slate-900 text-white overflow-hidden">

        <img src="<?= BASE_URL ?>/assets/images/bg1.jpg"
             alt="Billing Hospital"
             class="absolute inset-0 w-full h-full object-cover">

        <div class="absolute inset-0 bg-slate-900/50"></div>

        <div class="relative z-10 flex flex-col justify-between w-full p-14">

            <span class="text-xl font-semibold tracking-tight">Hospital Billing System</span>

            <h2 class="text-4xl font-bold leading-tight max-w-lg">
                Patient information and billing, all in one place.
            </h2>

            <p class="text-xs text-white/50">
                
            </p>
        </div>
    </div>

    
    
    
    <div class="flex items-center justify-center px-6 py-12 bg-white lg:col-span-3">

        <div class="w-full max-w-sm">

            
            <div class="mb-10">
              
                <h1 class="mt-6 text-2xl font-bold text-slate-900 tracking-tight">Sign in</h1>
              
            </div>

            <div id="alert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm border" role="alert"></div>

            <form id="loginForm" method="POST" action="/billing_hospital/api/login.php" novalidate class="space-y-5">

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input type="email" id="email" name="email" autocomplete="email" required
                           class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                           placeholder="admin@gmail.com">
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="email"></p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                     
                    </div>
                    <div class="relative">
                        <input type="password" id="password" name="password" autocomplete="current-password" required
                               class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 pr-11 text-sm text-slate-900 placeholder-slate-400
                                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="••••••••">
                        <button type="button" id="togglePassword"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-1.5 text-xs text-red-600 hidden" data-error-for="password"></p>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-600 select-none cursor-pointer">
                    <input type="checkbox" id="remember" name="remember"
                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Remember me
                </label>

                <button type="submit" id="submitBtn"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5
                               text-sm font-semibold text-white hover:bg-blue-700
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                               disabled:opacity-60 disabled:cursor-not-allowed transition">
                    <svg id="spinner" class="hidden animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span id="submitLabel">Sign in</span>
                </button>
            </form>

            <p class="mt-10 text-center text-xs text-slate-400 lg:hidden">
               
            </p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>