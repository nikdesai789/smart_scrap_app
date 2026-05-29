<?php
require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotStakeholder();

$stakeholder_id = $_SESSION['stakeholder_id'];
$message = "";

// 1. Handle Add/Edit Agent Request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ADD AGENT
    if (isset($_POST['add_agent'])) {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO agents (dealer_id, name, phone, email, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$stakeholder_id, $name, $phone, $email, $password]);
            $message = "<div class='alert alert-success border-0 shadow-sm alert-dismissible fade show' role='alert'>
                            <i class='fas fa-check-circle me-2'></i> Agent registered successfully!
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger border-0 shadow-sm alert-dismissible fade show' role='alert'>
                            <i class='fas fa-exclamation-triangle me-2'></i> Error: Phone or Email already registered.
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
        }
    }

    // EDIT AGENT
    if (isset($_POST['edit_agent'])) {
        $id = $_POST['agent_id'];
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        
        try {
            $stmt = $pdo->prepare("UPDATE agents SET name = ?, phone = ?, email = ? WHERE id = ? AND dealer_id = ?");
            $stmt->execute([$name, $phone, $email, $id, $stakeholder_id]);
            $message = "<div class='alert alert-info border-0 shadow-sm alert-dismissible fade show' role='alert'>
                            <i class='fas fa-info-circle me-2'></i> Agent details updated successfully.
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger border-0 shadow-sm alert-dismissible fade show' role='alert'>
                            <i class='fas fa-times-circle me-2'></i> Update failed: Contact details already in use.
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
        }
    }
}

// 2. Handle Delete Request
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM agents WHERE id = ? AND dealer_id = ?");
    $stmt->execute([$_GET['delete_id'], $stakeholder_id]);
    header("Location: manage_agents.php");
    exit();
}

// 3. Fetch All Agents
$stmt = $pdo->prepare("SELECT * FROM agents WHERE dealer_id = ? ORDER BY created_at DESC");
$stmt->execute([$stakeholder_id]);
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Agents | ScrapSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .main-card { border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; background: white; }
        .btn-add { background: #10b981; color: white; border: none; border-radius: 10px; padding: 10px 24px; font-weight: 600; transition: 0.3s; }
        .btn-add:hover { background: #059669; transform: translateY(-2px); color: white; }
        .table thead { background: #fdfdfd; text-transform: uppercase; font-size: 0.75rem; color: #64748b; letter-spacing: 0.5px; border-bottom: 2px solid #f1f5f9; }
        .agent-name { font-weight: 700; color: #1e293b; font-size: 1rem; }
        .status-pill { font-size: 0.7rem; padding: 4px 12px; border-radius: 50px; background: #ecfdf5; color: #059669; font-weight: 700; border: 1px solid #d1fae5; }
        .contact-info { font-size: 0.85rem; color: #475569; }
        .date-box { line-height: 1.2; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">Agent Management</h3>
            <a href="dashboard.php" class="text-secondary text-decoration-none small"><i class="fas fa-chevron-left me-1"></i> Back to Dashboard</a>
        </div>
        <button class="btn btn-add shadow-sm" data-bs-toggle="modal" data-bs-target="#agentModal">
            <i class="fas fa-user-plus me-2"></i>Register Agent
        </button>
    </div>

    <div id="alert-container">
        <?= $message ?>
    </div>

    <div class="card main-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Agent Info</th>
                        <th class="py-3">Contact Details</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 text-center">Joining Date</th>
                        <th class="text-end px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($agents)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No agents registered yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($agents as $agent): ?>
                        <tr>
                            <td class="px-4">
                                <div class="agent-name"><?= htmlspecialchars($agent['name']) ?></div>
                                <div class="small text-muted font-monospace">ID: #AGT-<?= $agent['id'] ?></div>
                            </td>
                            <td>
                                <div class="contact-info mb-1"><i class="fas fa-phone me-2 opacity-50"></i><?= htmlspecialchars($agent['phone']) ?></div>
                                <div class="contact-info"><i class="fas fa-envelope me-2 opacity-50"></i><?= htmlspecialchars($agent['email']) ?></div>
                            </td>
                            <td class="text-center"><span class="status-pill">ACTIVE</span></td>
                            <td class="text-center date-box">
                                <div class="fw-bold text-dark small"><?= date('d M, Y', strtotime($agent['created_at'])) ?></div>
                                <div class="text-muted" style="font-size: 11px;"><?= date('h:i A', strtotime($agent['created_at'])) ?></div>
                            </td>
                            <td class="text-end px-4">
                                <button class="btn btn-sm btn-outline-primary border-0 me-1" 
                                        onclick="editAgent(<?= htmlspecialchars(json_encode($agent)) ?>)" title="Edit Agent">
                                    <i class="fas fa-edit fs-6"></i>
                                </button>
                                <a href="?delete_id=<?= $agent['id'] ?>" class="btn btn-sm btn-outline-danger border-0" 
                                   onclick="return confirm('Are you sure you want to delete this agent? This cannot be undone.')" title="Delete Agent">
                                    <i class="fas fa-trash-alt fs-6"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="agentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form action="" method="POST" class="needs-validation" novalidate>
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold" id="modalTitle">Register New Agent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="agent_id" id="agent_id">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control rounded-3 py-2" required placeholder="Enter agent's full name">
                        <div class="invalid-feedback">Please enter a name.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control rounded-3 py-2" required placeholder="agent@email.com">
                        <div class="invalid-feedback">Please enter a valid email.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Phone Number</label>
                        <input type="tel" name="phone" id="phone" class="form-control rounded-3 py-2" required pattern="[0-9]{10}" placeholder="10-digit mobile number">
                        <div class="invalid-feedback">Please enter a 10-digit phone number.</div>
                    </div>

                    <div class="mb-0" id="passWrapper">
                        <label class="form-label small fw-bold">Login Password</label>
                        <input type="password" name="password" id="password" class="form-control rounded-3 py-2" minlength="6" placeholder="Create a password (min 6 chars)">
                        <div class="invalid-feedback">Password must be at least 6 characters.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" id="submitBtn" name="add_agent" class="btn btn-success w-100 rounded-pill py-2 fw-bold shadow-sm">Save Agent Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// 1. Bootstrap 5 Form Validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// 2. Edit Agent: Setup Modal for Update
function editAgent(agent) {
    const modalEl = document.getElementById('agentModal');
    const modal = new bootstrap.Modal(modalEl);
    
    document.getElementById('modalTitle').innerText = "Edit Agent Details";
    document.getElementById('submitBtn').name = "edit_agent";
    document.getElementById('submitBtn').innerText = "Update Agent Info";
    
    document.getElementById('agent_id').value = agent.id;
    document.getElementById('name').value = agent.name;
    document.getElementById('email').value = agent.email;
    document.getElementById('phone').value = agent.phone;
    
    // Hide password field for edits
    document.getElementById('passWrapper').style.display = "none";
    document.getElementById('password').required = false;
    
    modal.show();
}

// 3. Reset Modal state when closed
document.getElementById('agentModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalTitle').innerText = "Register New Agent";
    document.getElementById('submitBtn').name = "add_agent";
    document.getElementById('submitBtn').innerText = "Save Agent Account";
    document.getElementById('passWrapper').style.display = "block";
    document.getElementById('password').required = true;
    document.querySelector('form').reset();
    document.querySelector('form').classList.remove('was-validated');
});

// 4. Auto-hide alerts after 4 seconds
setTimeout(function() {
    let alert = document.querySelector('.alert');
    if (alert) {
        let bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    }
}, 4000);
</script>
</body>
</html>