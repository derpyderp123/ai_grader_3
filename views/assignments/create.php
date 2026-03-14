<?php
$page_title = 'Create Assignment';
ob_start();
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Create New Assignment</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/assignments/create">
                    <div class="mb-3">
                        <label for="title" class="form-label">Assignment Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required 
                               placeholder="e.g., Week 1: Python Basics">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required
                                  placeholder="Describe the assignment requirements, expected output, and any specific instructions..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="deadline" class="form-label">Deadline (Optional)</label>
                        <input type="datetime-local" class="form-control" id="deadline" name="deadline">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Grading Rubric (Optional - JSON format)</label>
                        <small class="text-muted d-block mb-2">
                            Define custom weights for grading criteria. If left empty, default weights will be used.
                        </small>
                        <textarea class="form-control" id="rubric" name="rubric" rows="6"
                                  placeholder='{
  "correctness": 40,
  "efficiency": 20,
  "security": 20,
  "style": 20
}'></textarea>
                        <div class="form-text">
                            Default rubric: Correctness (40%), Efficiency (20%), Security (20%), Style (20%)
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="/dashboard" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Create Assignment
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> How It Works</h6>
            </div>
            <div class="card-body">
                <ol class="mb-0">
                    <li>Students will see this assignment on their dashboard</li>
                    <li>They can upload code files (.py, .java, .cpp, .js, .c, .php)</li>
                    <li>AI automatically grades submissions based on your rubric</li>
                    <li>You can view all submissions and export grades to Excel</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
// Validate rubric JSON on input
document.getElementById('rubric').addEventListener('blur', function() {
    const value = this.value.trim();
    if (value) {
        try {
            JSON.parse(value);
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } catch (e) {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
        }
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
