<nav class="fixed top-0 left-0 w-full z-50 transition-all duration-300 bg-black/90 backdrop-blur-md border-b border-white/10 shadow-lg">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-20">
            <a href="index" class="flex items-center group">
                <img src="images/logo.png" alt="RO Village Logo" class="h-14 w-auto transition transform group-hover:scale-105 group-hover:brightness-110 duration-300 drop-shadow-[0_0_10px_rgba(255,193,7,0.5)]">
            </a>

            <div class="hidden md:flex space-x-8 items-center">
                <a href="index" class="text-gray-300 hover:text-yellow-400 font-medium transition text-sm uppercase tracking-wide">หน้าแรก</a>
                <a href="register" class="text-gray-300 hover:text-yellow-400 font-medium transition text-sm uppercase tracking-wide">สมัครสมาชิก</a>
                <a href="download" class="text-gray-300 hover:text-yellow-400 font-medium transition text-sm uppercase tracking-wide">ดาวน์โหลด</a>
                <a href="info" class="text-gray-300 hover:text-yellow-400 font-medium transition text-sm uppercase tracking-wide">ข้อมูลเซิร์ฟเวอร์</a>
                
                <?php if (isset($_SESSION['account_id'])): ?>
                    <a href="member" class="px-5 py-2 bg-yellow-500 text-black font-bold rounded-full hover:bg-yellow-400 transition shadow-[0_0_10px_rgba(255,193,7,0.4)]">
                        <i class="fas fa-user-cog mr-1"></i> จัดการไอดี
                    </a>
                <?php else: ?>
                    <a href="login" class="px-5 py-2 border border-yellow-500 text-yellow-500 font-bold rounded-full hover:bg-yellow-500 hover:text-black transition">
                        <i class="fas fa-sign-in-alt mr-1"></i> เข้าสู่ระบบ
                    </a>
                <?php endif; ?>
            </div>

            <button onclick="toggleMenu()" class="md:hidden text-white text-2xl focus:outline-none hover:text-yellow-400 transition">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>

    <div id="mobile-menu" class="hidden md:hidden bg-black/95 border-b border-white/10">
        <div class="px-4 pt-2 pb-4 space-y-2 text-center">
            <a href="index" class="block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-white/10 hover:text-yellow-400">หน้าแรก</a>
            <a href="register" class="block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-white/10 hover:text-yellow-400">สมัครสมาชิก</a>
            <a href="download" class="block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-white/10 hover:text-yellow-400">ดาวน์โหลด</a>
            <a href="info" class="block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-white/10 hover:text-yellow-400">ข้อมูลเซิร์ฟเวอร์</a>
            
            <div class="pt-4 border-t border-white/10 mt-2">
                <?php if (isset($_SESSION['account_id'])): ?>
                    <a href="member" class="block px-3 py-2 rounded-md text-base font-bold text-black bg-yellow-500 hover:bg-yellow-400 mx-10">จัดการไอดี</a>
                <?php else: ?>
                    <a href="login" class="block px-3 py-2 rounded-md text-base font-bold text-yellow-400 border border-yellow-500 hover:bg-yellow-500 hover:text-black mx-10">เข้าสู่ระบบ</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
    function toggleMenu() {
        const menu = document.getElementById('mobile-menu');
        menu.classList.toggle('hidden');
    }
</script>