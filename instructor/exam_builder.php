<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../auth/login.php");
    exit;
}
include_once '../includes/header.php';
?>

<div class="flex flex-col md:flex-row flex-1 bg-gray-50 h-screen overflow-hidden">
    <?php include_once '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto relative">
        <!-- Top Navigation / Actions -->
        <div
            class="sticky top-0 z-20 bg-white border-b border-gray-200 px-8 py-4 flex justify-between items-center shadow-sm">
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-xl font-bold text-gray-800">Exam Builder</h1>
            </div>
            <div class="flex gap-3">
                <button onclick="saveExam()"
                    class="bg-brand-blue text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-600 transition shadow-lg flex items-center gap-2">
                    <i class="fas fa-save"></i> Save Exam
                </button>
            </div>
        </div>

        <div class="max-w-4xl mx-auto p-8 pb-32">
            <!-- Exam Details Card -->
            <div
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-0 mb-6 overflow-hidden border-l-8 border-brand-blue">
                <div class="h-2 bg-brand-blue"></div>
                <div class="p-8">
                    <input type="text" id="examTitle" placeholder="Untitled Exam"
                        class="w-full text-3xl font-bold text-gray-800 placeholder-gray-300 border-b-2 border-transparent hover:border-gray-200 focus:border-brand-blue focus:outline-none transition mb-4 pb-2">

                    <textarea id="examDesc" placeholder="Form Description"
                        class="w-full text-gray-600 placeholder-gray-400 border-b-2 border-transparent hover:border-gray-200 focus:border-brand-blue focus:outline-none transition resize-none"
                        rows="2"></textarea>

                    <div class="mt-6 flex items-center gap-4">
                        <div class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-lg border border-gray-200">
                            <i class="far fa-clock text-gray-500"></i>
                            <input type="number" id="examDuration" value="60"
                                class="w-16 bg-transparent focus:outline-none text-gray-700 font-semibold text-center">
                            <span class="text-sm text-gray-500">minutes</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions Container -->
            <div id="questionsContainer" class="space-y-6">
                <!-- Questions will be injected here -->
            </div>

            <!-- Add Question Floating Action Button (Alternative placement) -->
            <div class="mt-8 flex justify-center">
                <button onclick="addQuestion()"
                    class="group bg-white border-2 border-dashed border-gray-300 rounded-xl p-4 w-full text-gray-400 hover:border-brand-blue hover:text-brand-blue transition flex flex-col items-center justify-center gap-2">
                    <i class="fas fa-plus-circle text-2xl group-hover:scale-110 transition-transform"></i>
                    <span class="font-semibold">Add Question</span>
                </button>
            </div>
        </div>
    </main>

    <!-- Floating Toolbar -->
    <div class="fixed right-8 bottom-8 flex flex-col gap-4">
        <button onclick="addQuestion()"
            class="w-14 h-14 bg-white text-gray-600 rounded-full shadow-lg border border-gray-100 flex items-center justify-center hover:bg-brand-blue hover:text-white transition transform hover:scale-110 tooltip"
            title="Add Question">
            <i class="fas fa-plus text-xl"></i>
        </button>
    </div>
</div>

<!-- Template for a Question Card -->
<template id="questionTemplate">
    <div
        class="question-card bg-white rounded-2xl shadow-sm border border-gray-100 p-6 relative group transform transition-all duration-300 hover:shadow-md">
        <!-- Drag Handle (Visual) -->
        <div
            class="absolute top-0 left-0 w-full h-6 flex justify-center items-center cursor-move opacity-0 group-hover:opacity-100 transition">
            <i class="fas fa-grip-lines text-gray-300"></i>
        </div>

        <div class="flex gap-4">
            <div class="flex-1 space-y-4">
                <!-- Question Text -->
                <div class="flex gap-4 items-start">
                    <span class="q-number text-gray-400 font-bold mt-3">1.</span>
                    <textarea
                        class="q-text w-full bg-gray-50 p-4 rounded-xl border border-gray-200 focus:border-brand-blue focus:bg-white focus:outline-none transition resize-none"
                        rows="2" placeholder="Question Text"></textarea>
                </div>

                <!-- Options -->
                <div class="space-y-3 pl-8">
                    <div class="flex items-center gap-3">
                        <input type="radio" name="temp_q" disabled
                            class="w-4 h-4 text-brand-blue bg-gray-100 border-gray-300">
                        <input type="text"
                            class="q-opt-a flex-1 border-b border-gray-200 focus:border-brand-blue focus:outline-none py-1 text-gray-700"
                            placeholder="Option A">
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="radio" name="temp_q" disabled
                            class="w-4 h-4 text-brand-blue bg-gray-100 border-gray-300">
                        <input type="text"
                            class="q-opt-b flex-1 border-b border-gray-200 focus:border-brand-blue focus:outline-none py-1 text-gray-700"
                            placeholder="Option B">
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="radio" name="temp_q" disabled
                            class="w-4 h-4 text-brand-blue bg-gray-100 border-gray-300">
                        <input type="text"
                            class="q-opt-c flex-1 border-b border-gray-200 focus:border-brand-blue focus:outline-none py-1 text-gray-700"
                            placeholder="Option C">
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="radio" name="temp_q" disabled
                            class="w-4 h-4 text-brand-blue bg-gray-100 border-gray-300">
                        <input type="text"
                            class="q-opt-d flex-1 border-b border-gray-200 focus:border-brand-blue focus:outline-none py-1 text-gray-700"
                            placeholder="Option D">
                    </div>
                </div>
            </div>

            <!-- Side Actions -->
            <div class="flex flex-col gap-2 border-l border-gray-100 pl-4">
                <div class="relative">
                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wide">Answer</label>
                    <select
                        class="q-correct block w-full mt-1 bg-green-50 text-green-700 border border-green-200 rounded-lg py-2 px-3 focus:outline-none font-bold cursor-pointer">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Bottom Actions -->
        <div
            class="flex justify-end mt-4 pt-4 border-t border-gray-50 gap-4 opacity-50 group-hover:opacity-100 transition">
            <button onclick="duplicateQuestion(this)" class="text-gray-500 hover:text-brand-blue" title="Duplicate"><i
                    class="far fa-copy"></i></button>
            <button onclick="deleteQuestion(this)" class="text-gray-500 hover:text-red-500" title="Delete"><i
                    class="far fa-trash-alt"></i></button>
            <div class="w-px h-6 bg-gray-200 mx-2"></div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500">Required</span>
                <input type="checkbox" checked class="toggle text-brand-blue">
            </div>
        </div>
    </div>
</template>

<script>
    let questionCount = 0;

    function addQuestion() {
        questionCount++;
        const template = document.getElementById('questionTemplate');
        const clone = template.content.cloneNode(true);
        const container = document.getElementById('questionsContainer');

        // Update number
        clone.querySelector('.q-number').textContent = questionCount + '.';

        // Ensure radio buttons in the UI don't interfere (visual only)
        const radios = clone.querySelectorAll('input[type="radio"]');
        radios.forEach(r => r.name = 'q_preview_' + questionCount);

        container.appendChild(clone);

        // Scroll to new
        window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    }

    function deleteQuestion(btn) {
        if (confirm('Delete this question?')) {
            const card = btn.closest('.question-card');
            card.remove();
            renumberQuestions();
        }
    }

    function duplicateQuestion(btn) {
        const card = btn.closest('.question-card');
        const clone = card.cloneNode(true);
        document.getElementById('questionsContainer').insertBefore(clone, card.nextSibling);
        renumberQuestions();
    }

    function renumberQuestions() {
        const questions = document.querySelectorAll('.question-card');
        questionCount = 0;
        questions.forEach((q) => {
            questionCount++;
            q.querySelector('.q-number').textContent = questionCount + '.';
            // Fix radios logic if needed
        });
    }

    function saveExam() {
        const title = document.getElementById('examTitle').value;
        const description = document.getElementById('examDesc').value;
        const subject = document.getElementById('examSubject').value || 'General';
        const duration = document.getElementById('examDuration').value;

        if (!title) {
            Swal.fire('Error', 'Please enter an exam title', 'error');
            return;
        }

        const questionsElements = document.querySelectorAll('.question-card');
        const questions = [];

        questionsElements.forEach(card => {
            questions.push({
                text: card.querySelector('.q-text').value,
                options: {
                    A: card.querySelector('.q-opt-a').value,
                    B: card.querySelector('.q-opt-b').value,
                    C: card.querySelector('.q-opt-c').value,
                    D: card.querySelector('.q-opt-d').value,
                },
                correct: card.querySelector('.q-correct').value
            });
        });

        if (questions.length === 0) {
            Swal.fire('Warning', 'Please add at least one question', 'warning');
            return;
        }

        // Send Data
        fetch('save_exam_logic.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title, description, subject, duration, questions
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success', 'Exam saved successfully!', 'success')
                        .then(() => window.location.href = 'dashboard.php');
                } else {
                    Swal.fire('Error', data.message || 'Something went wrong', 'error');
                }
            })
            .catch(err => Swal.fire('Error', 'Network error', 'error'));
    }

    // Initialize with 1 question
    window.onload = () => addQuestion();
</script>

<?php include_once '../includes/footer.php'; ?>