<?php
session_start();
include_once 'config/db.php';

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'instructor') {
        header("Location: instructor/dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'student') {
        header("Location: student/dashboard.php");
        exit;
    }
}

// Fetch Stats for Landing Page
try {
    $database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

    // Count Students
    $studentCount = $database->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();

    // Count Exams
    $examCount = $database->query("SELECT COUNT(*) FROM exams")->fetchColumn();

    // Count Unique Troops
    $troopCount = $database->query("SELECT COUNT(DISTINCT troop_id) FROM users WHERE troop_id IS NOT NULL")->fetchColumn();

    // Total Results (Exams Completed)
    $completedCount = $database->query("SELECT COUNT(*) FROM student_exams WHERE status = 'completed'")->fetchColumn();

} catch (Exception $e) {
    $studentCount = $examCount = $troopCount = $completedCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diani Sea Scouts — Kwale County</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0a4d68 0%, #088395 50%, #00b4d8 100%);
            min-height: 100vh;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes wave {

            0%,
            100% {
                transform: rotate(0deg);
            }

            25% {
                transform: rotate(5deg);
            }

            75% {
                transform: rotate(-5deg);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes ripple {
            0% {
                transform: scale(0.8);
                opacity: 1;
            }

            100% {
                transform: scale(2.5);
                opacity: 0;
            }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        .animate-wave {
            animation: wave 3s ease-in-out infinite;
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .animate-slideInLeft {
            animation: slideInLeft 0.8s ease-out forwards;
        }

        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-strong {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .gradient-text {
            background: linear-gradient(135deg, #00b4d8, #0077b6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hover-lift:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .wave-bg {
            position: relative;
            overflow: hidden;
        }

        .wave-bg::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 200%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120'%3E%3Cpath d='M0,60 C150,90 350,0 600,60 C850,120 1050,30 1200,60 L1200,120 L0,120 Z' fill='rgba(255,255,255,0.1)'/%3E%3C/svg%3E");
            background-size: 50% 100%;
            animation: wave-move 15s linear infinite;
            opacity: 0.3;
        }

        @keyframes wave-move {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        .btn-ripple {
            position: relative;
            overflow: hidden;
        }

        .btn-ripple::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-ripple:hover::before {
            width: 300px;
            height: 300px;
        }

        .scroll-reveal {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.8s ease-out;
        }

        .scroll-reveal.active {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>

<body class="text-gray-900">

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4 animate-slideInLeft">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-2xl flex items-center justify-center shadow-xl animate-wave">
                        <img src="images/logo.png" alt="Logo" class="w-full h-full object-contain rounded-2xl">
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">Diani Sea Scouts</h1>
                        <p class="text-xs text-cyan-200">Kwale County • Stewards of the Sea</p>
                    </div>
                </div>

                <div class="hidden md:flex items-center gap-6">
                    <a href="#about" class="text-white hover:text-cyan-300 transition font-medium">About</a>
                    <a href="#programs" class="text-white hover:text-cyan-300 transition font-medium">Programs</a>
                    <a href="auth/login.php"
                        class="text-white hover:text-cyan-300 transition font-medium border-l border-white/20 pl-6">Login</a>
                    <a href="#join"
                        class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-6 py-2.5 rounded-full font-bold hover:shadow-2xl hover:scale-105 transition btn-ripple">Join
                        the Crew</a>
                </div>

                <button class="md:hidden text-white" onclick="toggleMobileMenu()">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="fixed inset-0 z-40 glass hidden">
        <div class="flex flex-col items-center justify-center h-full gap-8 text-2xl text-white">
            <a href="#about" class="hover:text-cyan-300 transition" onclick="toggleMobileMenu()">About</a>
            <a href="#programs" class="hover:text-cyan-300 transition" onclick="toggleMobileMenu()">Programs</a>
            <a href="auth/login.php" class="hover:text-cyan-300 transition" onclick="toggleMobileMenu()">Login
                Portal</a>
            <a href="#join" class="bg-gradient-to-r from-emerald-500 to-teal-600 px-8 py-3 rounded-full font-bold"
                onclick="toggleMobileMenu()">Join the Crew</a>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-6 wave-bg">
        <div class="max-w-7xl mx-auto">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div class="animate-fadeInUp">
                    <h2 class="text-5xl md:text-6xl font-bold text-white mb-6 leading-tight">
                        Empowering Young <span class="text-cyan-300">Guardians</span> of the Sea
                    </h2>
                    <p class="text-xl text-cyan-100 mb-8 leading-relaxed">
                        Adventure. Leadership. Conservation. Join young people from Kwale County protecting our coastal
                        environment, learning maritime skills, and serving the community.
                    </p>

                    <div class="flex flex-wrap gap-4 mb-8">
                        <a href="#join"
                            class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-8 py-4 rounded-full font-bold text-lg hover:shadow-2xl hover:scale-105 transition btn-ripple">
                            Join Now
                        </a>
                        <a href="auth/login.php"
                            class="glass-strong text-blue-700 px-8 py-4 rounded-full font-bold text-lg hover:shadow-xl hover:scale-105 transition flex items-center gap-2">
                            <i class="fas fa-sign-in-alt"></i> Portal Login
                        </a>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="glass-strong rounded-2xl p-5 hover-lift">
                            <div class="text-3xl mb-2">📅</div>
                            <div class="font-bold text-blue-700">Upcoming</div>
                            <div class="text-sm text-blue-600">Beach clean-up • Feb 14, 2026</div>
                        </div>
                        <div class="glass-strong rounded-2xl p-5 hover-lift">
                            <div class="text-3xl mb-2">🎓</div>
                            <div class="font-bold text-blue-700">Training</div>
                            <div class="text-sm text-blue-600">Seamanship & first aid • Monthly</div>
                        </div>
                    </div>
                </div>

                <div class="animate-float">
                    <div class="glass-strong rounded-3xl p-6 shadow-2xl">
                        <div
                            class="bg-gradient-to-br from-cyan-400 to-blue-600 rounded-2xl h-80 flex items-center justify-center overflow-hidden">
                            <img src="images/logo2.png" alt="Diani Sea Scouts Logo"
                                class="w-full h-full object-contain"> </div>
                        <p class="text-center font-semibold text-blue-700 mt-4">Building Tomorrow's Ocean Leaders</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 scroll-reveal">
                <div class="glass-strong rounded-2xl p-8 text-center hover-lift">
                    <div class="text-4xl font-bold gradient-text mb-2">500+</div>
                    <div class="text-blue-700 font-medium">Young Scouts</div>
                </div>
                <div class="glass-strong rounded-2xl p-8 text-center hover-lift">
                    <div class="text-4xl font-bold gradient-text mb-2">50+</div>
                    <div class="text-blue-700 font-medium">Beach Cleanups</div>
                </div>
                <div class="glass-strong rounded-2xl p-8 text-center hover-lift">
                    <div class="text-4xl font-bold gradient-text mb-2">1000+</div>
                    <div class="text-blue-700 font-medium">Trees Planted</div>
                </div>
                <div class="glass-strong rounded-2xl p-8 text-center hover-lift">
                    <div class="text-4xl font-bold gradient-text mb-2">15+</div>
                    <div class="text-blue-700 font-medium">Community Projects</div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="glass-strong rounded-3xl p-10 md:p-16 scroll-reveal">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div class="order-2 md:order-1">
                        <h3 class="text-4xl font-bold gradient-text mb-6">Who We Are</h3>
                        <p class="text-blue-700 text-lg mb-4 leading-relaxed">
                            Diani Sea Scouts is a youth-led initiative based in Kwale County that promotes marine
                            conservation, seamanship, leadership development and community service.
                        </p>
                        <p class="text-blue-700 text-lg mb-6 leading-relaxed">
                            We teach young people to be responsible guardians of our coastal environment while building
                            character and practical skills.
                        </p>
                        <div
                            class="bg-gradient-to-r from-cyan-50 to-blue-50 rounded-2xl p-6 border-l-4 border-cyan-500">
                            <div class="font-bold text-blue-800 mb-2">Our Mission</div>
                            <p class="text-blue-700">To inspire and equip the youth of Diani to protect the ocean and
                                serve the community with courage and integrity.</p>
                        </div>
                    </div>

                    <div class="order-1 md:order-2">
                        <div
                            class="bg-gradient-to-br from-cyan-400 to-blue-600 rounded-2xl h-96 flex items-center justify-center shadow-2xl">
                            <img src="images/Diani.png" alt="Diani Beach"
                                class="w-full h-full object-cover rounded-2xl">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Programs Section -->
    <section id="programs" class="py-20 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16 scroll-reveal">
                <h3 class="text-5xl font-bold text-white mb-4">Our Programs</h3>
                <p class="text-xl text-cyan-200">Building skills, character, and environmental stewardship</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="glass-strong rounded-3xl p-8 hover-lift scroll-reveal">
                    <div class="text-6xl mb-6">🌊</div>
                    <h4 class="text-2xl font-bold text-blue-700 mb-4">Marine Conservation</h4>
                    <p class="text-blue-600 leading-relaxed">
                        Beach clean-ups, mangrove planting, awareness campaigns and citizen-science projects to protect
                        local marine life.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <span
                            class="bg-cyan-100 text-cyan-700 px-3 py-1 rounded-full text-sm font-medium">Clean-ups</span>
                        <span
                            class="bg-cyan-100 text-cyan-700 px-3 py-1 rounded-full text-sm font-medium">Planting</span>
                        <span
                            class="bg-cyan-100 text-cyan-700 px-3 py-1 rounded-full text-sm font-medium">Research</span>
                    </div>
                </div>

                <div class="glass-strong rounded-3xl p-8 hover-lift scroll-reveal">
                    <div class="text-6xl mb-6">⛵</div>
                    <h4 class="text-2xl font-bold text-blue-700 mb-4">Adventure Training</h4>
                    <p class="text-blue-600 leading-relaxed">
                        Sailing, kayaking, navigation, swimming and safety training designed to build confidence and
                        teamwork.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <span
                            class="bg-teal-100 text-teal-700 px-3 py-1 rounded-full text-sm font-medium">Sailing</span>
                        <span
                            class="bg-teal-100 text-teal-700 px-3 py-1 rounded-full text-sm font-medium">Kayaking</span>
                        <span class="bg-teal-100 text-teal-700 px-3 py-1 rounded-full text-sm font-medium">Safety</span>
                    </div>
                </div>

                <div class="glass-strong rounded-3xl p-8 hover-lift scroll-reveal">
                    <div class="text-6xl mb-6">🤝</div>
                    <h4 class="text-2xl font-bold text-blue-700 mb-4">Community Service</h4>
                    <p class="text-blue-600 leading-relaxed">
                        Volunteering with local schools and community groups, offering environmental education and
                        practical help.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <span
                            class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-sm font-medium">Education</span>
                        <span
                            class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-sm font-medium">Outreach</span>
                        <span
                            class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-sm font-medium">Service</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="py-20 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16 scroll-reveal">
                <h3 class="text-5xl font-bold text-white mb-4">Our Journey</h3>
                <p class="text-xl text-cyan-200">Moments that inspire and unite us</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 scroll-reveal">
                <div
                    class="bg-gradient-to-br from-cyan-300 to-blue-500 rounded-2xl h-64 flex items-center justify-center hover-lift cursor-pointer">
                    <div class="text-center text-white">
                        <div class="text-6xl mb-2">📸</div>
                        <p class="font-semibold">Beach Training</p>
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-teal-300 to-cyan-500 rounded-2xl h-64 flex items-center justify-center hover-lift cursor-pointer">
                    <div class="text-center text-white">
                        <div class="text-6xl mb-2">🌱</div>
                        <p class="font-semibold">Tree Planting</p>
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-blue-400 to-indigo-500 rounded-2xl h-64 flex items-center justify-center hover-lift cursor-pointer">
                    <div class="text-center text-white">
                        <div class="text-6xl mb-2">🚣</div>
                        <p class="font-semibold">Water Sports</p>
                    </div>
                </div>
                <div
                    class="bg-gradient-to-br from-emerald-400 to-teal-500 rounded-2xl h-64 flex items-center justify-center hover-lift cursor-pointer">
                    <div class="text-center text-white">
                        <div class="text-6xl mb-2">🏆</div>
                        <p class="font-semibold">Achievements</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Join Section -->
    <section id="join" class="py-20 px-6">
        <div class="max-w-5xl mx-auto">
            <div class="glass-strong rounded-3xl p-10 md:p-16 scroll-reveal">
                <div class="text-center mb-10">
                    <h3 class="text-5xl font-bold gradient-text mb-4">Join the Crew</h3>
                    <p class="text-xl text-blue-600">Ready to make waves? Become part of something bigger.</p>
                </div>

                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div>
                        <p class="text-blue-700 text-lg mb-6 leading-relaxed">
                            Register as a member, volunteer or sponsor — we welcome everyone who cares about the sea and
                            our community.
                        </p>

                        <div class="space-y-4">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-full flex items-center justify-center text-white text-xl">
                                    ✉️</div>
                                <div>
                                    <div class="font-semibold text-blue-800">Email</div>
                                    <div class="text-blue-600">diani.seascouts@example.com</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-full flex items-center justify-center text-white text-xl">
                                    📱</div>
                                <div>
                                    <div class="font-semibold text-blue-800">Phone</div>
                                    <div class="text-blue-600">+254 700 000 000</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-cyan-50 to-blue-50 rounded-2xl p-8">
                        <form onsubmit="event.preventDefault(); handleSubmit();" class="space-y-4">
                            <div>
                                <input type="text" id="nameInput" placeholder="Your name" required
                                    class="w-full px-5 py-4 rounded-xl border-2 border-blue-200 focus:border-blue-500 outline-none transition text-blue-800 font-medium">
                            </div>
                            <div>
                                <input type="tel" id="phoneInput" placeholder="Phone / WhatsApp" required
                                    class="w-full px-5 py-4 rounded-xl border-2 border-blue-200 focus:border-blue-500 outline-none transition text-blue-800 font-medium">
                            </div>
                            <button type="submit"
                                class="w-full bg-gradient-to-r from-blue-600 to-cyan-600 text-white px-8 py-4 rounded-xl font-bold text-lg hover:shadow-2xl hover:scale-105 transition btn-ripple">
                                Sign Up Now
                            </button>
                        </form>

                        <div class="text-center mt-6">
                            <a href="https://wa.me/254700000000" target="_blank"
                                class="inline-flex items-center gap-2 text-emerald-600 hover:text-emerald-700 font-semibold transition">
                                <span class="text-2xl">💬</span> Message on WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 px-6 mt-20">
        <div class="max-w-7xl mx-auto">
            <div class="glass-strong rounded-3xl p-10">
                <div class="grid md:grid-cols-3 gap-8 mb-8">
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <div
                                class="w-12 h-12 bg-gradient-to-br from-cyan-400 to-blue-600 rounded-xl flex items-center justify-center">
                                <span class="text-2xl">🌊</span>
                            </div>
                            <div>
                                <div class="font-bold text-blue-800">Diani Sea Scouts</div>
                                <div class="text-sm text-blue-600">Kwale County</div>
                            </div>
                        </div>
                        <p class="text-blue-600 text-sm">Bravery • Service • The Sea</p>
                    </div>

                    <div>
                        <div class="font-bold text-blue-800 mb-3">Quick Links</div>
                        <div class="space-y-2">
                            <a href="#about" class="block text-blue-600 hover:text-blue-800 transition">About Us</a>
                            <a href="#programs" class="block text-blue-600 hover:text-blue-800 transition">Programs</a>
                            <a href="#gallery" class="block text-blue-600 hover:text-blue-800 transition">Gallery</a>
                            <a href="#join" class="block text-blue-600 hover:text-blue-800 transition">Join</a>
                        </div>
                    </div>

                    <div>
                        <div class="font-bold text-blue-800 mb-3">Follow Us</div>
                        <div class="flex gap-4">
                            <a href="#"
                                class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-full flex items-center justify-center text-white hover:scale-110 transition">f</a>
                            <a href="#"
                                class="w-10 h-10 bg-gradient-to-br from-pink-500 to-purple-500 rounded-full flex items-center justify-center text-white hover:scale-110 transition">📷</a>
                            <a href="#"
                                class="w-10 h-10 bg-gradient-to-br from-red-500 to-pink-500 rounded-full flex items-center justify-center text-white hover:scale-110 transition">▶️</a>
                        </div>
                    </div>
                </div>

                <div class="border-t border-blue-200 pt-6 text-center text-blue-600 text-sm">
                    © 2026 Diani Sea Scouts — All rights reserved
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(10, 77, 104, 0.95)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.1)';
            }
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Scroll reveal animation
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));

        // Form submission
        function handleSubmit() {
            const name = document.getElementById('nameInput').value;
            const phone = document.getElementById('phoneInput').value;

            alert(`Thank you, ${name}! We'll contact you at ${phone} soon. Welcome to the Diani Sea Scouts family! 🌊`);

            document.getElementById('nameInput').value = '';
            document.getElementById('phoneInput').value = '';
        }
    </script>
</body>

</html>