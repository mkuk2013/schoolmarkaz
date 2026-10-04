<?php
declare(strict_types=1);
/** Quiz questions — edit shell (shared logic: includes/quizlib.php). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/quizlib.php';
require_role('admin');
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
quiz_handle_edit_post($id, 'admin/quiz_edit.php', true);
layout_top('Quiz Questions', 'quizzes');
quiz_render_edit($id, 'admin/quizzes.php', true);
layout_bottom();
