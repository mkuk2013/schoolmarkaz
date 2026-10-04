<?php
declare(strict_types=1);
/** Quizzes — list/create shell (shared logic: includes/quizlib.php). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/quizlib.php';
require_role('admin');
quiz_handle_list_post('admin/quizzes.php', true);
layout_top('Quizzes', 'quizzes');
quiz_render_list('admin/quizzes.php', 'admin/quiz_edit.php', 'admin/quiz_results.php', true);
layout_bottom();
