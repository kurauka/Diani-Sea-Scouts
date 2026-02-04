<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

$student_id = $_GET['id'] ?? $_SESSION['user_id'];

if ($_SESSION['role'] === 'student' && $student_id != $_SESSION['user_id']) {
    exit('Unauthorized');
}

$stmt = $database->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    exit('Student not found.');
}

$qrData = json_encode([
    'id' => $student['id'],
    'name' => $student['name'],
    'role' => 'student',
    'valid' => true,
    'school' => $student['school'] ?? 'Diani Sea Scouts'
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID CARD - <?php echo strtoupper(htmlspecialchars($student['name'])); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;600;700;800&family=Cinzel:wght@700;900&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-navy: #002B36;
            --brand-blue: #0077B6;
            --brand-accent: #00B4D8;
            --gold-bright: #FFD700;
        }
        @page { size: auto; margin: 0; }
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; font-family: 'Lexend', sans-serif; }
        
        .cr80 { 
            width: 3.375in; 
            height: 2.125in; 
            border-radius: 14px;
            overflow: hidden;
            position: relative;
            background: white;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .side-accent {
            width: 8px;
            height: 100%;
            background: var(--brand-navy);
            position: absolute;
            left: 0;
            top: 0;
            z-index: 20;
        }

        .header-gradient {
            background: linear-gradient(90deg, #0A4D68 0%, #0077B6 100%);
        }

        .info-label {
            font-size: 6px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #64748b;
            font-weight: 800;
        }

        .info-value {
            font-size: 10px;
            color: #0f172a;
            font-weight: 700;
            line-height: 1;
        }

        .photo-container {
            width: 85px;
            height: 105px;
            border-radius: 8px;
            padding: 2px;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
        }

        @media print {
            body { background: white; }
            .no-print { display: none; }
            .print-area { margin: 20px; }
        }
    </style>
</head>
<body class="bg-slate-50 flex flex-col items-center justify-center min-h-screen">

    <div class="no-print mb-12 text-center p-6 bg-white rounded-3xl shadow-sm border border-slate-200 min-w-[400px]">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mb-2 italic">Diani Sea Scouts</h1>
        <p class="text-slate-500 font-medium mb-6">Premium Identity Card Generator</p>
        <button onclick="window.print()" class="bg-slate-900 hover:bg-black text-white px-10 py-4 rounded-2xl font-black shadow-2xl transition-all transform hover:-translate-y-1 active:scale-95 flex items-center gap-3 mx-auto">
            <i class="fas fa-print text-xl"></i> PRINT IDENTITY CARD
        </button>
    </div>

    <div class="print-area flex flex-col lg:flex-row gap-12">
        
        <!-- SIDE A: THE PROFESSIONAL FRONT -->
        <div class="cr80 flex flex-col">
            <div class="side-accent"></div>
            
            <!-- Clear Header Section -->
            <div class="header-gradient h-14 w-full flex items-center px-4 pl-6 relative">
                 <div class="flex items-center gap-3">
                    <!-- High Visibility Logo Container -->
                    <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center p-1 shadow-lg ring-2 ring-white/20">
                        <img src="../assets/images/logo.png" class="w-full h-full object-contain">
                    </div>
                    <div class="text-white">
                        <h2 class="text-[11px] font-['Cinzel'] font-black tracking-[0.15em] leading-tight">DIANI SEA SCOUTS</h2>
                        <p class="text-[7px] font-bold text-cyan-300 uppercase tracking-widest opacity-80">Membership Identity Card</p>
                    </div>
                 </div>
                 <div class="absolute right-3 top-3">
                     <span class="text-[7px] text-white/50 font-mono tracking-tighter">EST. 2026</span>
                 </div>
            </div>

            <div class="flex-1 flex px-5 pt-3 gap-5 relative z-10">
                <!-- User Photo -->
                <div class="photo-container overflow-hidden bg-slate-100 flex-shrink-0">
                     <?php if(!empty($student['profile_image'])): ?>
                        <img src="../<?php echo htmlspecialchars($student['profile_image']); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-slate-300">
                             <i class="fas fa-user-shield text-4xl"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Presentable User Information -->
                <div class="flex-1 space-y-3 py-1">
                    <div class="pb-1 border-b-2 border-slate-100">
                        <span class="info-label">Member Full Name</span>
                        <h3 class="text-[15px] font-extrabold text-slate-900 tracking-tight leading-tight truncate">
                            <?php echo htmlspecialchars($student['name']); ?>
                        </h3>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-0.5">
                            <span class="info-label">Member ID</span>
                             <p class="info-value text-blue-600 font-mono tracking-tight"><?php echo str_pad($student['id'], 6, '0', STR_PAD_LEFT); ?></p>
                        </div>
                        <div class="space-y-0.5">
                            <span class="info-label">Troop ID</span>
                            <p class="info-value"><?php echo htmlspecialchars($student['troop_id'] ?? 'DS-01'); ?></p>
                        </div>
                    </div>

                    <div class="space-y-0.5">
                        <span class="info-label">Associated Institution</span>
                        <p class="info-value truncate"><?php echo htmlspecialchars($student['school'] ?? 'Diani Scouts Academy'); ?></p>
                    </div>

                    <div class="flex items-center gap-2 mt-2">
                        <div class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md border border-blue-100 font-black text-[7px] uppercase tracking-widest">
                            Official Cadet
                        </div>
                    </div>
                </div>
            </div>

            <!-- Authentic Holographic-style strip -->
            <div class="h-1.5 bg-gradient-to-r from-yellow-400 via-orange-500 to-yellow-600 w-full opacity-60"></div>
        </div>

        <!-- SIDE B: THE SMART BACK -->
        <div class="cr80 border border-slate-200 flex flex-col">
            <!-- Security Strip -->
            <div class="w-full h-9 bg-slate-900 mt-4 flex items-center px-6">
                <span class="text-[6px] text-white/20 font-mono tracking-widest">IDENTITY VERIFICATION SYSTEM • DIANI SEA SCOUTS • SECURE ACCESS</span>
            </div>

            <div class="flex-1 flex p-5 gap-6">
                <div class="flex-1 flex flex-col justify-between">
                    <div class="space-y-3">
                         <div class="bg-slate-50 p-2 rounded-lg border border-slate-100">
                             <p class="text-[6px] leading-relaxed text-slate-500 font-medium">This identity document remains the property of Diani Sea Scouts. Unauthorized use or reproduction is strictly prohibited.</p>
                         </div>
                         <div class="text-center font-black text-red-600 uppercase tracking-widest text-[9px] border-2 border-red-50 py-1.5 rounded-xl bg-red-50/20">
                             Valid for 2026 Only
                         </div>
                    </div>

                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-[6px] text-slate-400 uppercase font-black tracking-[0.2em] mb-1">Official Authorization</p>
                            <div class="w-32 h-6 border-b-2 border-slate-200 relative">
                                <!-- Subtle Seal -->
                                <img src="../assets/images/logo.png" class="h-10 absolute -top-4 right-0 opacity-10 grayscale">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- High Priority QR Module -->
                <div class="flex flex-col items-center justify-center">
                    <div class="p-2 bg-white rounded-2xl shadow-inner border border-slate-100">
                         <div id="qrcode"></div>
                    </div>
                    <div class="mt-2 text-center">
                        <span class="text-[6px] font-black text-slate-900 border border-slate-900 px-2 py-0.5 rounded uppercase">Verified</span>
                    </div>
                </div>
            </div>

            <!-- Branded Bottom -->
            <div class="h-4 bg-slate-900 flex items-center justify-center gap-4">
                 <span class="text-[5px] text-white/50 tracking-[0.4em] font-bold uppercase">Marine Excellence</span>
                 <i class="fas fa-anchor text-white/30 text-[8px]"></i>
                 <span class="text-[5px] text-white/50 tracking-[0.4em] font-bold uppercase">Leadership</span>
                 <i class="fas fa-ship text-white/30 text-[8px]"></i>
                 <span class="text-[5px] text-white/50 tracking-[0.4em] font-bold uppercase">Diani Legacy</span>
            </div>
        </div>

    </div>

    <!-- Link FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script type="text/javascript">
        // Precise QR Generation
        new QRCode(document.getElementById("qrcode"), {
            text: '<?php echo $qrData; ?>',
            width: 68,
            height: 68,
            colorDark : "#0f172a",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    </script>
</body>
</html>