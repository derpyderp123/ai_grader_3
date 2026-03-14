<?php
$page_title = 'Grade Report - ' . $assignment['title'];
ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="/assignments/<?= $assignment['id'] ?>"><?= htmlspecialchars($assignment['title']) ?></a></li>
                <li class="breadcrumb-item active">Grade Report</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-file-earmark-spreadsheet"></i> Grade Report: <?= htmlspecialchars($assignment['title']) ?></h5>
                <div>
                    <a href="/reports/<?= $assignment['id'] ?>/export" class="btn btn-success btn-sm">
                        <i class="bi bi-download"></i> Export Excel
                    </a>
                    <a href="/assignments/<?= $assignment['id'] ?>" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Student</th>
                                <th>Submission Date</th>
                                <th>Status</th>
                                <th>AI Model</th>
                                <th>Score</th>
                                <th>Correctness</th>
                                <th>Efficiency</th>
                                <th>Security</th>
                                <th>Style</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data)): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted">No submissions yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data as $submission): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($submission['student_name']) ?></strong><br>
                                            <small class="text-muted">ID: <?= $submission['student_id'] ?></small>
                                        </td>
                                        <td><?= date('M d, Y H:i', strtotime($submission['created_at'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $submission['status'] === 'completed' ? 'success' : ($submission['status'] === 'grading' ? 'primary' : 'warning') ?>">
                                                <?= ucfirst($submission['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($submission['ai_model_used'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php if ($submission['grade']): ?>
                                                <span class="badge bg-<?= $submission['grade']['score'] >= 70 ? 'success' : ($submission['grade']['score'] >= 50 ? 'warning' : 'danger') ?> grade-badge">
                                                    <?= $submission['grade']['score'] ?>/100
                                                </span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($submission['grade']): ?>
                                                <?php $feedback = json_decode($submission['grade']['feedback_json'], true); ?>
                                                <?= $feedback['correctness'] ?? '-' ?>/40
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($submission['grade']): ?>
                                                <?= $feedback['efficiency'] ?? '-' ?>/20
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($submission['grade']): ?>
                                                <?= $feedback['security'] ?? '-' ?>/20
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($submission['grade']): ?>
                                                <?= $feedback['style'] ?? '-' ?>/20
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($submission['grade']): ?>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="showDetails(<?= htmlspecialchars(json_encode($feedback)) ?>)">
                                                    <i class="bi bi-eye"></i> Details
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Detailed Feedback</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modalContent">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function showDetails(feedback) {
    const modalContent = document.getElementById('modalContent');
    
    let html = '<div class="alert alert-info"><strong>AI Summary:</strong><br>' + 
               (feedback.summary || 'No summary available') + '</div>';
    
    // Metrics
    html += '<h6>Scoring Breakdown</h6>';
    html += '<div class="row mb-3">';
    html += '<div class="col-md-6">';
    html += '<div class="d-flex justify-content-between"><span>Correctness:</span><strong>' + 
            (feedback.correctness || 0) + '/40</strong></div>';
    html += '<div class="progress mb-2"><div class="progress-bar" style="width: ' + 
            ((feedback.correctness || 0) / 40 * 100) + '%"></div></div>';
    html += '</div>';
    html += '<div class="col-md-6">';
    html += '<div class="d-flex justify-content-between"><span>Efficiency:</span><strong>' + 
            (feedback.efficiency || 0) + '/20</strong></div>';
    html += '<div class="progress mb-2"><div class="progress-bar bg-success" style="width: ' + 
            ((feedback.efficiency || 0) / 20 * 100) + '%"></div></div>';
    html += '</div>';
    html += '</div>';
    html += '<div class="row mb-3">';
    html += '<div class="col-md-6">';
    html += '<div class="d-flex justify-content-between"><span>Security:</span><strong>' + 
            (feedback.security || 0) + '/20</strong></div>';
    html += '<div class="progress mb-2"><div class="progress-bar bg-info" style="width: ' + 
            ((feedback.security || 0) / 20 * 100) + '%"></div></div>';
    html += '</div>';
    html += '<div class="col-md-6">';
    html += '<div class="d-flex justify-content-between"><span>Style:</span><strong>' + 
            (feedback.style || 0) + '/20</strong></div>';
    html += '<div class="progress mb-2"><div class="progress-bar bg-warning" style="width: ' + 
            ((feedback.style || 0) / 20 * 100) + '%"></div></div>';
    html += '</div>';
    html += '</div>';
    
    // Bugs
    if (feedback.bugs && feedback.bugs.length > 0) {
        html += '<h6 class="text-danger"><i class="bi bi-bug"></i> Issues Found</h6>';
        html += '<ul class="list-group mb-3">';
        feedback.bugs.forEach(bug => {
            html += '<li class="list-group-item list-group-item-danger">' + bug + '</li>';
        });
        html += '</ul>';
    }
    
    // Suggestions
    if (feedback.suggestions && feedback.suggestions.length > 0) {
        html += '<h6 class="text-primary"><i class="bi bi-lightbulb"></i> Improvement Suggestions</h6>';
        html += '<ul class="list-group">';
        feedback.suggestions.forEach(suggestion => {
            html += '<li class="list-group-item list-group-item-info">' + suggestion + '</li>';
        });
        html += '</ul>';
    }
    
    modalContent.innerHTML = html;
    
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    modal.show();
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
