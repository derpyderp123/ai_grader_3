<?php
$page_title = $assignment['title'];
ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($assignment['title']) ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?= htmlspecialchars($assignment['title']) ?></h5>
                <?php if ($assignment['deadline']): ?>
                    <span class="badge bg-<?= strtotime($assignment['deadline']) < time() ? 'danger' : 'warning' ?>">
                        <i class="bi bi-calendar-event"></i> Due: <?= date('M d, Y H:i', strtotime($assignment['deadline'])) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <h6>Description</h6>
                <p><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
                
                <h6 class="mt-4">Grading Rubric</h6>
                <?php 
                $rubric = json_decode($assignment['rubric_json'], true);
                if ($rubric):
                ?>
                <div class="row">
                    <?php foreach ($rubric as $criteria => $weight): ?>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex justify-content-between">
                                <span><?= ucfirst($criteria) ?></span>
                                <strong><?= $weight ?>%</strong>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" style="width: <?= $weight ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p>Default rubric applies: Correctness (40%), Efficiency (20%), Security (20%), Style (20%)</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Submissions List -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-inbox"></i> Submissions</h5>
            </div>
            <div class="card-body">
                <?php if (empty($submissions)): ?>
                    <p class="text-muted mb-0">No submissions yet.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($submissions as $sub): ?>
                            <div class="list-group-item list-group-item-action" id="submission-<?= $sub['id'] ?>">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php if ($_SESSION['role'] === 'teacher'): ?>
                                                Submission #<?= $sub['id'] ?> by <?= htmlspecialchars($sub['student_name'] ?? 'Unknown') ?>
                                            <?php else: ?>
                                                Your Submission
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> Submitted: <?= date('M d, Y H:i', strtotime($sub['created_at'])) ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-<?= $sub['status'] === 'completed' ? 'success' : ($sub['status'] === 'grading' ? 'primary' : 'warning') ?> mb-2">
                                            <?= ucfirst($sub['status']) ?>
                                        </span>
                                        <?php if ($sub['ai_model_used']): ?>
                                            <div><small class="text-muted">AI: <?= htmlspecialchars($sub['ai_model_used']) ?></small></div>
                                        <?php endif; ?>
                                        <?php if (isset($sub['grade']) && $sub['grade']): ?>
                                            <div class="mt-1">
                                                <span class="badge bg-success grade-badge"><?= $sub['grade']['score'] ?>/100</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (isset($sub['grade']) && $sub['grade']): ?>
                                    <?php $feedback = json_decode($sub['grade']['feedback_json'], true); ?>
                                    <div class="mt-3 p-3 bg-light rounded">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <strong><i class="bi bi-chat-left-text"></i> AI Summary:</strong>
                                                <p class="mb-2"><?= nl2br(htmlspecialchars($feedback['summary'] ?? 'No summary available')) ?></p>
                                            </div>
                                        </div>
                                        <?php if (!empty($feedback['bugs'])): ?>
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <strong class="text-danger"><i class="bi bi-bug"></i> Issues Found:</strong>
                                                <ul class="mb-2">
                                                    <?php foreach ($feedback['bugs'] as $bug): ?>
                                                        <li><?= htmlspecialchars($bug) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($feedback['suggestions'])): ?>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <strong class="text-primary"><i class="bi bi-lightbulb"></i> Suggestions:</strong>
                                                <ul class="mb-0">
                                                    <?php foreach ($feedback['suggestions'] as $suggestion): ?>
                                                        <li><?= htmlspecialchars($suggestion) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($sub['status'] === 'grading'): ?>
                                    <div class="mt-3">
                                        <div class="progress">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%">
                                                Grading in progress...
                                            </div>
                                        </div>
                                        <small class="text-muted">Please wait while AI evaluates your code...</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <?php if ($_SESSION['role'] === 'student' && $assignment['deadline'] && strtotime($assignment['deadline']) > time()): ?>
            <?php 
            $hasSubmitted = false;
            foreach ($submissions as $sub) {
                if (isset($sub['student_id']) && $sub['student_id'] == $_SESSION['user_id']) {
                    $hasSubmitted = true;
                    break;
                }
            }
            ?>
            <?php if (!$hasSubmitted): ?>
            <div class="card mb-4">
                <div class="card-body text-center">
                    <i class="bi bi-upload display-4 text-primary mb-3"></i>
                    <h5>Ready to Submit?</h5>
                    <p class="text-muted">Upload your code file for automatic AI grading</p>
                    <a href="/assignments/<?= $assignment['id'] ?>/submit" class="btn btn-primary">
                        <i class="bi bi-cloud-upload"></i> Submit Now
                    </a>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($_SESSION['role'] === 'teacher'): ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bi bi-file-earmark-spreadsheet"></i> Export Grades</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted">Download comprehensive Excel report with all submissions and AI feedback</p>
                <a href="/reports/<?= $assignment['id'] ?>/export" class="btn btn-success w-100">
                    <i class="bi bi-download"></i> Export to Excel
                </a>
                <hr>
                <a href="/reports/<?= $assignment['id'] ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-eye"></i> View Detailed Report
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header bg-warning">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Statistics</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <h3 class="text-primary"><?= count($submissions) ?></h3>
                        <small class="text-muted">Submissions</small>
                    </div>
                    <div class="col-6 mb-3">
                        <h3 class="text-success">
                            <?php 
                            $completed = 0;
                            $avgScore = 0;
                            foreach ($submissions as $sub) {
                                if ($sub['status'] === 'completed' && isset($sub['grade'])) {
                                    $completed++;
                                    $avgScore += $sub['grade']['score'];
                                }
                            }
                            echo $completed > 0 ? round($avgScore / $completed) : '-';
                            ?>
                        </h3>
                        <small class="text-muted">Avg Score</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($_SESSION['role'] === 'student'): ?>
<script>
// Live update for grading status
<?php foreach ($submissions as $sub): ?>
    <?php if ($sub['status'] === 'grading' || $sub['status'] === 'pending'): ?>
    (function() {
        const submissionId = <?= $sub['id'] ?>;
        const evtSource = new EventSource("/stream?submission_id=" + submissionId);
        
        evtSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            console.log('Update received:', data);
            
            if (data.status === 'completed' || data.status === 'failed') {
                location.reload();
                evtSource.close();
            }
        };
        
        evtSource.onerror = function() {
            console.log('Connection lost, retrying...');
            evtSource.close();
        };
    })();
    <?php endif; ?>
<?php endforeach; ?>
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
?>
