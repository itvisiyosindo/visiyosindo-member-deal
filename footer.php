    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 mt-auto py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-teal-600 flex items-center justify-center text-white text-sm">
                        <i class="fa-solid fa-notes-medical"></i>
                    </div>
                    <div>
                        <div class="font-bold text-slate-900 text-sm">Portal Member Visi Academy</div>
                        <div class="text-xs text-slate-500">Program Kemitraan & Referral Alkes PT Visi Yosindo Medikal</div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-6 text-xs text-slate-500">
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-headset text-teal-600"></i>
                        <span>Bantuan & Info: <strong class="text-slate-700">0812-xxxx-xxxx</strong></span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-globe text-teal-600"></i>
                        <a href="https://visiyosindo.id" target="_blank" class="hover:text-teal-600 transition">visiyosindo.id</a>
                    </span>
                    <span>&copy; <?= date('Y') ?> PT Visi Yosindo Medikal. All rights reserved.</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Mobile Menu Toggle Script -->
    <script>
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>
