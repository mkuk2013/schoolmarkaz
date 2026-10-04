<?php
declare(strict_types=1);
/** Quizzes — list/create shell (shared logic: includes/quizlib.php). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/quizlib.php';
require_role('teacher');
quiz_handle_list_post('teacher/quizzes.php', false);
layout_top('Quizzes', 'quizzes');
quiz_render_list('teacher/quizzes.php', 'teacher/quiz_edit.php', 'teacher/quiz_results.php', false);
layout_bottom();
