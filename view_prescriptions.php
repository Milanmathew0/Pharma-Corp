<?php
session_start();
include "connect.php";

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff', 'Manager'])) {
    header("Location: login.php");
    exit();
}

// Get all prescriptions with user information
$query = "SELECT p.*, u.name, u.email, u.phone 
          FROM prescriptions p 
          JOIN users u ON p.user_id = u.user_id 
          ORDER BY p.upload_date DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Prescriptions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .prescription-image {
            max-width: 200px;
            max-height: 200px;
            cursor: pointer;
        }
        .extracted-text {
            max-height: 200px;
            overflow-y: auto;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .modal-image {
            max-width: 100%;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>Prescription Management</h2>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Prescriptions</h5>
                <div>
                    <button class="btn btn-sm btn-outline-secondary me-2" id="filterAll">All</button>
                    <button class="btn btn-sm btn-outline-warning me-2" id="filterPending">Pending</button>
                    <button class="btn btn-sm btn-outline-success me-2" id="filterApproved">Approved</button>
                    <button class="btn btn-sm btn-outline-danger" id="filterRejected">Rejected</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Upload Date</th>
                                <th>Prescription</th>
                                <th>Extracted Text</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $statusClass = "";
                                    switch ($row['status']) {
                                        case 'pending':
                                            $statusClass = "bg-warning text-dark";
                                            break;
                                        case 'approved':
                                            $statusClass = "bg-success text-white";
                                            break;
                                        case 'rejected':
                                            $statusClass = "bg-danger text-white";
                                            break;
                                    }
                                    
                                    echo "<tr class='prescription-row " . $row['status'] . "-row'>";
                                    echo "<td>" . $row['id'] . "</td>";
                                    echo "<td>" . htmlspecialchars($row['name'] ?? $row['user_id']) . "<br>";
                                    echo "<small>" . htmlspecialchars($row['email']) . "</small><br>";
                                    echo "<small>" . htmlspecialchars($row['phone'] ?? 'No phone') . "</small></td>";
                                    echo "<td>" . date('M d, Y H:i', strtotime($row['upload_date'])) . "</td>";
                                    
                                    echo "<td>";
                                    if (!empty($row['file_name'])) {
                                        echo "<img src='view_prescription_image.php?id=" . $row['id'] . "' class='prescription-image' 
                                              data-bs-toggle='modal' data-bs-target='#imageModal' 
                                              data-img-id='" . $row['id'] . "' alt='Prescription'>";
                                    } else {
                                        echo "<span class='text-muted'>No image</span>";
                                    }
                                    echo "</td>";
                                    
                                    echo "<td><div class='extracted-text'>" . nl2br(htmlspecialchars($row['extracted_text'])) . "</div></td>";
                                    echo "<td><span class='badge " . $statusClass . "'>" . ucfirst($row['status']) . "</span></td>";
                                    
                                    echo "<td>";
                                    if ($row['status'] === 'pending') {
                                        echo "<button class='btn btn-sm btn-success mb-1 approve-btn' data-id='" . $row['id'] . "'><i class='bi bi-check-circle'></i> Approve</button><br>";
                                        echo "<button class='btn btn-sm btn-danger reject-btn' data-id='" . $row['id'] . "'><i class='bi bi-x-circle'></i> Reject</button>";
                                    } else {
                                        echo "<button class='btn btn-sm btn-secondary reset-btn' data-id='" . $row['id'] . "'><i class='bi bi-arrow-counterclockwise'></i> Reset</button>";
                                    }
                                    echo "</td>";
                                    
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center'>No prescriptions found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Prescription Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="modal-image" alt="Prescription Image">
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle image modal
            $('.prescription-image').click(function() {
                const imgId = $(this).data('img-id');
                $('#modalImage').attr('src', 'view_prescription_image.php?id=' + imgId);
            });
            
            // Filter buttons
            $('#filterAll').click(function() {
                $('.prescription-row').show();
            });
            
            $('#filterPending').click(function() {
                $('.prescription-row').hide();
                $('.pending-row').show();
            });
            
            $('#filterApproved').click(function() {
                $('.prescription-row').hide();
                $('.approved-row').show();
            });
            
            $('#filterRejected').click(function() {
                $('.prescription-row').hide();
                $('.rejected-row').show();
            });
            
            // Handle prescription status updates
            $('.approve-btn').click(function() {
                updatePrescriptionStatus($(this).data('id'), 'approved');
            });
            
            $('.reject-btn').click(function() {
                updatePrescriptionStatus($(this).data('id'), 'rejected');
            });
            
            $('.reset-btn').click(function() {
                updatePrescriptionStatus($(this).data('id'), 'pending');
            });
            
            function updatePrescriptionStatus(id, status) {
                if (confirm('Are you sure you want to mark this prescription as ' + status + '?')) {
                    $.ajax({
                        url: 'update_prescription_status.php',
                        type: 'POST',
                        data: {
                            id: id,
                            status: status
                        },
                        success: function(response) {
                            try {
                                const result = JSON.parse(response);
                                if (result.success) {
                                    alert(result.message);
                                    location.reload();
                                } else {
                                    alert('Error: ' + result.message);
                                }
                            } catch (e) {
                                alert('Error processing the request');
                            }
                        },
                        error: function() {
                            alert('Error connecting to the server');
                        }
                    });
                }
            }
        });
    </script>
</body>
</html> 