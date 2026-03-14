<?php
$page_title = 'Dashboard';
ob_start();
?>

<div class="row">
    <div class="col-12">
        <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
        <p class="text-muted">Welcome, <?= htmlspecialchars($data['user']['username']) ?>!</p>
    </div>
</div>

<?php if ($data['user']['role'] === 'teacher'): ?>
<!-- Teacher Dashboard -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card card-hover h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> My Assignments</h5>
            </div>
            <div class="card-body">
                <?php if (empty($data['assignments'])): ?>
                    <p class="text-muted">No assignments created yet.</p>
                    <a href="/assignments/create" class="btn btn-primary">Create Your First Assignment</a>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($data['assignments'] as $assignment): ?>
                            <a href="/assignments/<?= $assignment['id'] ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($assignment['title']) ?></h6>
                                    <small class="text-muted"><?= date('M d', strtotime($assignment['created_at'])) ?></small>
                                </div>
                                <p class="mb-1 text-truncate"><?= htmlspecialchars(substr($assignment['description'], 0, 80)) ?>...</p>
                                <small class="text-primary">
                                    <i class="bi bi-inbox"></i> 
                                    <?php 
                                    $subCount = 0;
                                    if (isset($data['recent_submissions'])) {
                                        foreach ($data['recent_submissions'] as $s) {
                                            if ($s['assignment_id'] == $assignment['id']) $subCount++;
                                        }
                                    }
                                    echo $subCount . ' submissions';
                                    ?>
                                </small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card card-hover h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Submissions</h5>
            </div>
            <div class="card-body">
                <?php if (empty($data['recent_submissions'])): ?>
                    <p class="text-muted">No recent submissions.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Assignment</th>
                                    <th>Status</th>
                                    <th>Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($data['recent_submissions'], 0, 5) as $sub): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($sub['student_name'] ?? 'Unknown') ?></td>
                                        <td><?= htmlspecialchars($sub['assignment_title'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $sub['status'] === 'completed' ? 'success' : ($sub['status'] === 'grading' ? 'primary' : 'warning') ?>">
                                                <?= ucfirst($sub['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($sub['grade']): ?>
                                                <strong><?= $sub['grade']['score'] ?>/100</strong>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Student Dashboard -->
<div class="row mt-4">
    <div class="col-md-8">
        <div class="card card-hover">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-list-task"></i> Available Assignments</h5>
            </div>
            <div class="card-body">
                <?php if (empty($data['assignments'])): ?>
                    <p class="text-muted">No active assignments available.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($data['assignments'] as $assignment): ?>
                            <a href="/assignments/<?= $assignment['id'] ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($assignment['title']) ?></h6>
                                    <?php if ($assignment['deadline']): ?>
                                        <small class="text-danger">
                                            <i class="bi bi-calendar-event"></i> Due: <?= date('M d, Y', strtotime($assignment['deadline'])) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars(substr($assignment['description'], 0, 100)) ?>...</p>
                                <small class="text-primary">Click to view details and submit</small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card card-hover">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-upload"></i> My Submissions</h5>
            </div>
            <div class="card-body">
                <?php if (empty($data['my_submissions'])): ?>
                    <p class="text-muted">No submissions yet.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach (array_slice($data['my_submissions'], 0, 5) as $sub): ?>
                            <a href="/assignments/<?= $sub['assignment_id'] ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between">
                                    <small><?= date('M d', strtotime($sub['created_at'])) ?></small>
                                    <span class="badge bg-<?= $sub['status'] === 'completed' ? 'success' : ($sub['status'] === 'grading' ? 'primary' : 'warning') ?>">
                                        <?= ucfirst($sub['status']) ?>
                                    </span>
                                </div>
                                <small class="text-muted">Assignment #<?= $sub['assignment_id'] ?></small>
                                <?php if ($sub['grade']): ?>
                                    <div class="mt-1">
                                        <strong>Score: <?= $sub['grade']['score'] ?>/100</strong>
                                    </div>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
