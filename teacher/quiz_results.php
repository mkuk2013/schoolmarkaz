<?php
declare(strict_types=1);
/** Quiz results shell (shared logic: includes/quizlib.php). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/quizlib.php';
require_role('teacher');
$id = (int) ($_GET['id'] ?? 0);
layout_top('Quiz Results', 'quizzes');
quiz_render_results($id, 'teacher/quizzes.php', false);
layout_bottom();
