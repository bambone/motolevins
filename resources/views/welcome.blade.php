<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RentBase - Business OS</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-slate-50 text-slate-900 antialiased font-sans overflow-x-hidden selection:bg-indigo-100 selection:text-indigo-900">

    <!-- Header Navigation -->
    <header class="w-full fixed top-0 z-50 bg-white/80 backdrop-blur-md border-b border-slate-900/5">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <span class="font-bold text-lg tracking-tight">RentBase</span>
            </div>
            
            @if (Route::has('login'))
                <nav class="flex items-center gap-6">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="text-sm font-medium px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 shadow-sm transition-all shadow-premium hover:-translate-y-0.5">Start Free Trial</a>
                        @endif
                    @endauth
                </nav>
            @endif
        </div>
    </header>

    <main class="w-full pt-32 pb-20">
        <!-- Hero Section -->
        <section class="max-w-7xl mx-auto px-6 relative">
            <!-- Background glow -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-500/10 blur-3xl rounded-full pt-10 pointer-events-none"></div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <!-- Left Column -->
                <div class="relative z-10 max-w-xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-100/50 text-indigo-700 text-xs font-semibold tracking-wide uppercase mb-6">
                        <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                        RentBase OS 2.0 is live
                    </div>
                    <h1 class="text-5xl lg:text-6xl font-bold tracking-tight text-navy mb-6 leading-[1.1]">
                        The Ultimate <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-indigo-400">Control Center</span> for scaling operations.
                    </h1>
                    <p class="text-lg text-slate-600 mb-8 leading-relaxed">
                        Transition from fragmented tools to a unified business OS. Manage applications, scheduling, and clients through one elegant, intelligent interface.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center gap-4">
                        <a href="#" class="w-full sm:w-auto px-8 py-3.5 bg-navy text-white rounded-lg font-medium shadow-premium hover:-translate-y-0.5 hover:shadow-glow transition-all text-center">
                            Get Started Free
                        </a>
                        <a href="#" class="w-full sm:w-auto px-8 py-3.5 bg-white text-slate-700 border border-slate-200 rounded-lg font-medium hover:bg-slate-50 transition-colors flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Watch Demo
                        </a>
                    </div>
                    
                    <!-- Trust Strip -->
                    <div class="mt-12 flex items-center gap-4 text-sm text-slate-500 font-medium">
                        <span>Trusted by leading modern teams:</span>
                        <div class="flex gap-4 opacity-60">
                            <!-- Dummy logos -->
                            <div class="font-bold flex items-center gap-1"><div class="w-2 h-2 bg-slate-400 rounded-full"></div> Acem</div>
                            <div class="font-bold flex items-center gap-1"><div class="w-2 h-2 bg-slate-400 rounded-sm"></div> Globex</div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Hero Product Mockup) -->
                <div class="relative z-10 hidden lg:block perspective-normal">
                    <div class="glass-panel w-full rounded-2xl overflow-hidden transform relative hover:-translate-y-2 transition-transform duration-700">
                        <!-- Mockup Top Bar -->
                        <div class="h-12 border-b border-slate-900/5 bg-slate-50/50 flex items-center px-4 gap-2">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 rounded-full bg-slate-300"></div>
                                <div class="w-3 h-3 rounded-full bg-slate-300"></div>
                                <div class="w-3 h-3 rounded-full bg-slate-300"></div>
                            </div>
                            <!-- Search Bar Style -->
                            <div class="mx-auto w-1/2 h-6 bg-white rounded border border-slate-200 shadow-sm"></div>
                        </div>
                        <!-- Mockup Content Area -->
                        <div class="p-6 bg-slate-50/30 flex gap-6 h-[400px]">
                            <!-- Mockup Sidebar -->
                            <div class="w-1/4 flex flex-col gap-3">
                                <div class="h-8 w-full bg-white rounded-md border border-slate-100 shadow-sm flex items-center px-3 gap-2">
                                    <div class="w-4 h-4 rounded bg-indigo-100"></div>
                                    <div class="h-2 w-16 bg-slate-200 rounded"></div>
                                </div>
                                <div class="h-8 w-full bg-slate-100/50 rounded-md flex items-center px-3 gap-2">
                                    <div class="w-4 h-4 rounded bg-slate-200"></div>
                                    <div class="h-2 w-12 bg-slate-200 rounded"></div>
                                </div>
                                <div class="h-8 w-full bg-slate-100/50 rounded-md flex items-center px-3 gap-2">
                                    <div class="w-4 h-4 rounded bg-slate-200"></div>
                                    <div class="h-2 w-20 bg-slate-200 rounded"></div>
                                </div>
                            </div>
                            <!-- Mockup Main Stage -->
                            <div class="flex-1 flex flex-col gap-4">
                                <!-- Top stat row -->
                                <div class="flex gap-4 h-24">
                                    <div class="flex-1 bg-white border border-slate-100 shadow-sm rounded-xl p-4 flex flex-col justify-between">
                                        <div class="h-2 w-1/2 bg-slate-200 rounded"></div>
                                        <div class="h-8 w-3/4 bg-slate-800 rounded"></div>
                                    </div>
                                    <div class="flex-1 bg-indigo-600 shadow-sm rounded-xl p-4 flex flex-col justify-between">
                                        <div class="h-2 w-1/2 bg-indigo-300 rounded"></div>
                                        <div class="flex items-end gap-2 text-white">
                                            <div class="h-8 w-1/2 bg-white rounded"></div>
                                            <div class="h-4 w-1/4 bg-indigo-400 rounded mb-1"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Chart Area -->
                                <div class="flex-1 bg-white border border-slate-100 shadow-sm rounded-xl p-4 relative overflow-hidden">
                                     <div class="h-2 w-1/4 bg-slate-200 rounded mb-6"></div>
                                     <div class="absolute bottom-0 left-0 w-full h-2/3 bg-gradient-to-t from-indigo-50/50 to-transparent"></div>
                                     <!-- Fake bars -->
                                     <div class="flex items-end h-32 gap-3 mt-4 relative z-10">
                                        <div class="flex-1 bg-indigo-100 rounded-t h-1/3"></div>
                                        <div class="flex-1 bg-indigo-200 rounded-t h-1/2"></div>
                                        <div class="flex-1 bg-indigo-600 rounded-t h-full relative"><div class="absolute -top-6 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[10px] px-2 py-1 rounded">Active</div></div>
                                        <div class="flex-1 bg-indigo-200 rounded-t h-2/3"></div>
                                        <div class="flex-1 bg-indigo-100 rounded-t h-1/4"></div>
                                     </div>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Node Overlay -->
                        <div class="absolute -bottom-6 -right-6 glass-panel p-4 rounded-xl flex items-center gap-3 animate-pulse" style="animation-duration: 3s;">
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center border border-green-200">
                                <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-800">Lease Approved</div>
                                <div class="text-xs text-slate-500">Just now &bull; Unit 4B</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Divider -->
        <div class="max-w-7xl mx-auto px-6 mt-32 mb-16">
            <div class="h-px w-full bg-gradient-to-r from-transparent via-slate-200 to-transparent"></div>
        </div>

        <!-- Features Section (Micro UI Fragments) -->
        <section class="max-w-7xl mx-auto px-6 mb-32">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold tracking-tight text-navy mb-4">Focus on growth, we handle the workflow</h2>
                <p class="text-slate-600 max-w-2xl mx-auto text-lg">Every capability designed to eliminate manual tasks and elevate your business operations.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div class="glass-panel p-8 rounded-2xl hover:-translate-y-1 transition-transform cursor-pointer group">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center mb-6 border border-indigo-100 group-hover:bg-indigo-600 transition-colors">
                        <svg class="w-6 h-6 text-indigo-600 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-navy mb-3">Smart Automation</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">Set up rules to automatically process applications and send tenant communications without lifting a finger.</p>
                </div>
                <!-- Card 2 -->
                <div class="glass-panel p-8 rounded-2xl hover:-translate-y-1 transition-transform cursor-pointer group">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center mb-6 border border-indigo-100 group-hover:bg-indigo-600 transition-colors">
                        <svg class="w-6 h-6 text-indigo-600 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-navy mb-3">Finances Reimagined</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">Instant payment reconciliations, deep revenue analytics, and frictionless ledger tracking for total clarity.</p>
                </div>
                <!-- Card 3 -->
                <div class="glass-panel p-8 rounded-2xl hover:-translate-y-1 transition-transform cursor-pointer group">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center mb-6 border border-indigo-100 group-hover:bg-indigo-600 transition-colors">
                        <svg class="w-6 h-6 text-indigo-600 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-navy mb-3">Tenant Portal</h3>
                    <p class="text-slate-600 leading-relaxed text-sm">Provide an exquisite branded experience for your tenants to pay rent and submit maintenance requests.</p>
                </div>
            </div>
        </section>

        <!-- Deep Control Section (Central OS Diagram) -->
        <section class="max-w-7xl mx-auto px-6 mb-32 relative">
             <div class="glass-panel-dark p-12 lg:p-20 rounded-3xl overflow-hidden relative group">
                <!-- Subtle grid background inside dark section -->
                <div class="absolute inset-0 opacity-10" style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(to right, #fff 1px, #161615 1px); background-size: 40px 40px;"></div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center relative z-10">
                    <div>
                        <h2 class="text-3xl lg:text-4xl font-bold tracking-tight text-white mb-6">Total Control under one unified architecture</h2>
                        <p class="text-slate-400 text-lg leading-relaxed mb-8">
                            Unlike traditional software, RentBase organizes all interconnected components into one fluid intelligence. Experience a system that works on your behalf.
                        </p>
                        <a href="#" class="inline-flex pb-1 border-b border-indigo-500 font-medium text-indigo-400 hover:text-indigo-300 transition-colors">See how it connects &rarr;</a>
                    </div>
                    <div class="relative h-[300px] lg:h-[400px] flex items-center justify-center">
                        <div class="absolute w-[300px] h-[300px] bg-indigo-600/30 blur-3xl rounded-full pointer-events-none"></div>
                        
                        <!-- Connecting Lines (SVG overlay) -->
                        <svg class="absolute inset-0 w-full h-full text-indigo-400/20" stroke="currentColor">
                           <line x1="50%" y1="50%" x2="20%" y2="20%" stroke-width="2"/>
                           <line x1="50%" y1="50%" x2="80%" y2="25%" stroke-width="2"/>
                           <line x1="50%" y1="50%" x2="25%" y2="80%" stroke-width="2"/>
                           <line x1="50%" y1="50%" x2="75%" y2="75%" stroke-width="2"/>
                        </svg>

                        <!-- Core Node -->
                        <div class="relative z-10 w-20 h-20 lg:w-24 lg:h-24 bg-indigo-600 rounded-2xl shadow-premium flex items-center justify-center ring-4 ring-indigo-400/20 animate-pulse" style="animation-duration: 4s;">
                             <span class="font-bold text-white text-xl tracking-wider">OS</span>
                        </div>
                        
                        <!-- Satellite 1 -->
                        <div class="absolute top-10 left-[10%] lg:left-[15%] w-16 h-16 bg-white/10 backdrop-blur border border-white/20 rounded-xl flex items-center justify-center text-xs text-white shadow-xl hover:-translate-y-1 transition-transform cursor-pointer">CRM</div>
                        <!-- Satellite 2 -->
                        <div class="absolute top-[15%] right-[10%] lg:right-[15%] w-16 h-16 bg-white/10 backdrop-blur border border-white/20 rounded-xl flex items-center justify-center text-xs text-white shadow-xl hover:-translate-y-1 transition-transform cursor-pointer">Billing</div>
                        <!-- Satellite 3 -->
                        <div class="absolute bottom-[10%] left-[15%] lg:left-[20%] w-16 h-16 bg-white/10 backdrop-blur border border-white/20 rounded-xl flex items-center justify-center text-xs text-white shadow-xl hover:-translate-y-1 transition-transform cursor-pointer">Docs</div>
                        <!-- Satellite 4 -->
                        <div class="absolute bottom-[15%] right-[15%] lg:right-[20%] w-16 h-16 bg-white/10 backdrop-blur border border-white/20 rounded-xl flex items-center justify-center text-xs text-white shadow-xl hover:-translate-y-1 transition-transform cursor-pointer">Maint</div>
                    </div>
                </div>
             </div>
        </section>
    </main>
    
    <!-- Footer CTA -->
    <footer class="bg-white border-t border-slate-900/5 pt-20 pb-10">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <h2 class="text-4xl font-bold tracking-tight text-navy mb-6">Ready to upgrade your operations?</h2>
            <p class="text-slate-600 max-w-xl mx-auto text-lg mb-10">Join the thousands of modern property managers using RentBase to outpace the competition.</p>
            <a href="#" class="inline-flex items-center justify-center px-8 py-4 text-lg bg-indigo-600 text-white rounded-lg font-medium shadow-premium hover:bg-indigo-700 hover:-translate-y-0.5 transition-all">
                Start your free 14-day trial
            </a>
            <p class="mt-6 text-sm text-slate-500">No credit card required. Cancel anytime.</p>
        </div>
        
        <div class="max-w-7xl mx-auto px-6 mt-20 pt-8 border-t border-slate-900/5 flex flex-col md:flex-row items-center justify-between text-slate-500 text-sm">
            <div class="flex items-center gap-2 mb-4 md:mb-0">
                <span class="font-bold text-slate-900 mr-4">RentBase</span>
                &copy; {{ date('Y') }} All rights reserved.
            </div>
            <div class="flex gap-6">
                <a href="#" class="hover:text-slate-900 transition-colors">Privacy Policy</a>
                <a href="#" class="hover:text-slate-900 transition-colors">Terms of Service</a>
                <a href="#" class="hover:text-slate-900 transition-colors">Contact Support</a>
            </div>
        </div>
    </footer>
</body>
</html>
