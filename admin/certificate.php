<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    exit('Unauthorized');
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

$result_id = $_GET['result_id'] ?? null;
if (!$result_id) {
    exit('Invalid Request');
}

// Fetch Result & Exam Details
$query = "SELECT se.score, se.completed_at, e.title, u.name as student_name 
          FROM student_exams se 
          JOIN exams e ON se.exam_id = e.id 
          JOIN users u ON se.student_id = u.id 
          WHERE se.id = :id";
$stmt = $database->prepare($query);
$stmt->execute([':id' => $result_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data || $data['score'] < 50) {
    exit('Certificate not available for this exam.');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sea Scout Certificate - <?php echo htmlspecialchars($data['student_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&family=Pinyon+Script&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap"
        rel="stylesheet">
    <style>
        @page {
            size: landscape;
            margin: 0;
        }

        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .bg-pattern {
            background-color: #ffffff;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23CAF0F8' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
    </style>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen p-0 m-0" onload="window.print()">

    <div
        class="bg-white w-[1123px] h-[794px] relative shadow-2xl flex flex-col items-center justify-between p-12 box-border border-[16px] border-[#0A4D68] overflow-hidden bg-pattern">

        <!-- Decorative Corners -->
        <div class="absolute top-0 left-0 w-32 h-32 border-t-[16px] border-l-[16px] border-[#00B4D8] z-10"></div>
        <div class="absolute top-0 right-0 w-32 h-32 border-t-[16px] border-r-[16px] border-[#00B4D8] z-10"></div>
        <div class="absolute bottom-0 left-0 w-32 h-32 border-b-[16px] border-l-[16px] border-[#00B4D8] z-10"></div>
        <div class="absolute bottom-0 right-0 w-32 h-32 border-b-[16px] border-r-[16px] border-[#00B4D8] z-10"></div>

        <!-- Logo -->
        <div class="absolute top-12 left-1/2 transform -translate-x-1/2 z-20">
            <img src="../assets/images/logo.png" alt="Diani Sea Scouts Logo" class="h-32 object-contain drop-shadow-md">
        </div>

        <!-- Watermark -->
        <div class="absolute inset-0 flex items-center justify-center opacity-[0.05] pointer-events-none z-0">
            <img src="../assets/images/logo.png" alt="Watermark" class="h-[600px] object-contain grayscale">
        </div>

        <!-- Content Container -->
        <div class="z-10 w-full h-full flex flex-col items-center justify-center pt-24 pb-8 px-16">

            <h1 class="text-5xl font-black text-[#0A4D68] font-['Cinzel'] mb-2 uppercase tracking-wide mt-8">Certificate
                of Completion</h1>

            <div class="w-24 h-1.5 bg-[#0077B6] my-6 rounded-full"></div>

            <!-- Recipient -->
            <p class="text-xl text-gray-500 font-serif italic mb-4">This acknowledges that</p>

            <div class="relative w-full max-w-4xl text-center border-b-2 border-dashed border-[#00B4D8]/50 pb-2 mb-6">
                <h2 class="text-6xl text-[#0A4D68] font-['Playfair_Display'] font-bold italic">
                    <?php echo htmlspecialchars($data['student_name']); ?>
                </h2>
            </div>

            <p class="text-xl text-gray-500 font-serif italic mb-6">has successfully passed the examination for</p>

            <!-- Exam Title -->
            <h3
                class="text-4xl font-bold text-[#0077B6] font-['Cinzel'] mb-6 uppercase max-w-3xl text-center drop-shadow-sm">
                <?php echo htmlspecialchars($data['title']); ?>
            </h3>

            <!-- Score Badge -->
            <div
                class="bg-[#CAF0F8] border border-[#00B4D8] px-8 py-3 rounded-full mb-12 shadow-inner inline-flex items-center gap-3">
                <i class="fas fa-star text-[#0077B6]"></i>
                <span class="text-[#0A4D68] uppercase tracking-widest text-sm font-bold">Score Achieved:</span>
                <span class="text-2xl font-black text-[#0077B6]"><?php echo $data['score']; ?>%</span>
                <i class="fas fa-star text-[#0077B6]"></i>
            </div>

            <!-- Footer Section -->
            <div class="flex justify-between w-full max-w-5xl mt-auto items-end px-12 pb-8">

                <!-- Date -->
                <div class="text-center w-64">
                    <p class="font-bold text-[#0A4D68] text-lg border-b-2 border-[#0A4D68] pb-1 mx-4">
                        <?php echo date('F d, Y', strtotime($data['completed_at'])); ?></p>
                    <p class="text-[#0077B6] font-['Cinzel'] text-xs font-bold uppercase tracking-wider mt-2">Date
                        Awarded</p>
                </div>

                <!-- Seal -->
                <div class="relative -mb-4">
                    <div
                        class="w-32 h-32 bg-[#0A4D68] rounded-full flex items-center justify-center border-4 border-[#CAF0F8] shadow-lg">
                        <div
                            class="text-center text-[#CAF0F8] border border-[#CAF0F8] rounded-full w-24 h-24 flex flex-col items-center justify-center p-2">
                            <i class="fas fa-anchor text-2xl mb-1"></i>
                            <span class="text-[8px] font-bold font-['Cinzel'] leading-tight">OFFICIAL
                                CERTIFICATION</span>
                        </div>
                    </div>
                </div>

                <!-- Signature -->
                <div class="text-center w-64">
                    <div class="font-['Pinyon_Script'] text-3xl text-[#0A4D68] border-b-2 border-[#0A4D68] pb-1 mx-4">
                        Administrator</div>
                    <p class="text-[#0077B6] font-['Cinzel'] text-xs font-bold uppercase tracking-wider mt-2">Authorized
                        Signature</p>
                </div>

            </div>

        </div>

    </div>

</body>

</html>