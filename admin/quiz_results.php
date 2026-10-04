<?php
declare(strict_types=1);
/** Quiz results shell (shared logic: includes/quizlib.php). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/quizlib.php';
require_role('admin');
$id = (int) ($_GET['id'] ?? 0);
layout_top('Quiz Results', 'quizzes');
quiz_render_results($id, 'admin/quizzes.php', true);
layout_bottom();
