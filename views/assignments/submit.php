<?php
$page_title = 'Submit Assignment';
ob_start();
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-upload"></i> Submit Your Code</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle"></i> Assignment: <?= htmlspecialchars($assignment['title']) ?></h6>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
                </div>
                
                <form method="POST" action="/assignments/<?= $assignment['id'] ?>/submit" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="code_file" class="form-label">Upload Code File *</label>
                        <input type="file" class="form-control" id="code_file" name="code_file" 
                               accept=".py,.java,.cpp,.js,.c,.php" required>
                        <div class="form-text">
                            Allowed formats: Python (.py), Java (.java), C++ (.cpp), JavaScript (.js), C (.c), PHP (.php)
                            <br>Maximum file size: 5MB
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <small>
                            <i class="bi bi-exclamation-triangle"></i> 
                            By submitting, you confirm this is your own work. The code will be automatically graded by AI.
                        </small>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="/assignments/<?= $assignment['id'] ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cloud-upload"></i> Submit Assignment
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bi bi-lightbulb"></i> Grading Criteria</h6>
            </div>
            <div class="card-body">
                <?php 
                $rubric = json_decode($assignment['rubric_json'], true);
                if ($rubric):
                ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($rubric as $criteria => $weight): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= ucfirst($criteria) ?>
                            <span class="badge bg-primary rounded-pill"><?= $weight ?>%</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="mb-0">Default rubric will be applied: Correctness (40%), Efficiency (20%), Security (20%), Style (20%)</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Validate file size
document.getElementById('code_file').addEventListener('change', function() {
    const file = this.files[0];
    if (file && file.size > 5 * 1024 * 1024) {
        alert('File size must be less than 5MB');
        this.value = '';
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
?>
