<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: login.php");
    exit();
}

// Set a default username if not set
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = 'Customer';
}

include "connect.php";

// Clear the form submission flag when displaying the page
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    unset($_SESSION['form_submitted']);
}

// Add cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Pharma Corp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c9db7;
            --secondary-color: #858796;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: url('pic/3.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            z-index: 0;
        }

        .navbar {
            background-color: var(--primary-color);
            padding: 1rem 2rem;
            position: relative;
            z-index: 1;
        }

        .container {
            position: relative;
            z-index: 1;
            padding: 2rem 15px;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            border: 1px solid rgba(44, 157, 183, 0.1);
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(44, 157, 183, 0.2);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            border-bottom: none;
        }

        .card-header i {
            font-size: 1.8rem;
            margin-right: 10px;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: white;
        }

        .card-content {
            padding: 1.5rem;
        }

        .order-list li, .notification-list li {
            background: rgba(44, 157, 183, 0.05);
            border-radius: 10px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .order-list li:hover, .notification-list li:hover {
            background: rgba(44, 157, 183, 0.1);
        }

        .status {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
        }

        .status-pending {
            background: #fff7ed;
            color: #f97316;
        }

        .status-available {
            background: #f0fdf4;
            color: #22c55e;
        }

        .status-processing {
            background: #fef3c7;
            color: #f59e0b;
        }

        .medicines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .medicine-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .medicine-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(44, 157, 183, 0.2);
        }

        .btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(44, 157, 183, 0.2);
        }

        .icon-large {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .prescription-upload {
            border: 2px dashed var(--primary-color);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(44, 157, 183, 0.02);
            margin-bottom: 1.5rem;
        }

        .prescription-upload:hover {
            background: rgba(44, 157, 183, 0.08);
            border-color: #2589a0;
            transform: translateY(-2px);
        }

        .prescription-upload i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            transition: transform 0.3s ease;
        }

        .prescription-upload:hover i {
            transform: translateY(-5px);
        }

        .prescription-upload h4 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .prescription-upload p {
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }

        .prescription-upload .text-muted {
            font-size: 0.8rem;
        }

        .prescription-upload .text-muted {
            font-size: 0.8rem;
        }

        .prescription-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .prescription-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: rgba(44, 157, 183, 0.05);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
        }

        .prescription-item:hover {
            background: rgba(44, 157, 183, 0.1);
            transform: translateX(5px);
        }

        .prescription-item i {
            font-size: 1.2rem;
            margin-right: 0.75rem;
            color: var(--primary-color);
        }

        .prescription-status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .prescription-status i {
            font-size: 0.7rem;
            margin: 0;
        }

        .status-pending {
            background: #fff7ed;
            color: #f97316;
        }

        .status-approved {
            background: #f0fdf4;
            color: #22c55e;
        }

        .status-rejected {
            background: #fef2f2;
            color: #ef4444;
        }

        #uploadProgress {
            height: 6px;
            border-radius: 3px;
            margin-top: 1rem;
            display: none;
        }

        #uploadProgress .progress-bar {
            background-color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .medicines-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }

        .extracted-text-preview {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 4px;
            white-space: pre-line;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1 style="color: white;"><i class="fas fa-clinic-medical"></i> Pharma-Corp</h1>
        <div class="nav-links">
            <a href="search-medicines.php" class="nav-link d-inline-block me-3" style="color: white;"><i class="fas fa-search"></i> Search Medicines</a>
        </div>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span style="color: white;">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="customer-profile.php" class="btn btn-info">
                <i class="bi bi-person"></i> My Profile
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </nav>

    

        

       

        <!-- Profile Management -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-shopping-cart"></i>
                <h2>Available Medicines</h2>
            </div>
            <div class="card-content">
                <!-- Simple Search Form -->
                <div class="p-3 bg-light border-bottom">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="medicinesTable">
                        <thead class="table-primary">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Medicine Name</th>
                                <th scope="col">Stock</th>
                                <th scope="col">Price per Unit</th>
                                <th scope="col">Manufacturer</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $query = "SELECT * FROM medicines";
                                $result = $conn->query($query);
                                $counter = 1;
                                
                                if ($result && $result->num_rows > 0) {
                                    while ($medicine = $result->fetch_assoc()) {
                                        $status_class = $medicine['stock_quantity'] > 10 ? 'success' : 
                                                    ($medicine['stock_quantity'] > 0 ? 'warning' : 'danger');
                                        $status_text = $medicine['stock_quantity'] > 10 ? 'In Stock' : 
                                                    ($medicine['stock_quantity'] > 0 ? 'Low Stock' : 'Out of Stock');
                                        
                                        echo '<tr>
                                                <td>'. $counter++ .'</td>
                                                <td>'. htmlspecialchars($medicine['name']) .'</td>
                                                <td>'. htmlspecialchars($medicine['stock_quantity']) .'</td>
                                                <td>₹'. number_format($medicine['price_per_unit'], 2) .'</td>
                                                <td>'. htmlspecialchars($medicine['company']) .'</td>
                                                <td><span class="badge bg-'. $status_class .'">'. $status_text .'</span></td>
                                            </tr>';
                                    }
                                    $result->free();
                                } else {
                                    echo '<tr><td colspan="6" class="text-center">No medicines available at the moment.</td></tr>';
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Feature Cards -->
        <div class="row g-4">
            <!-- Search Medicines -->
            <div class="col-md-4">
                <div class="card h-100 feature-card">
                    <div class="card-body text-center">
                        <i class="bi bi-search icon-large text-primary"></i>
                        <h5 class="card-title">Search Medicines</h5>
                        <p class="card-text">Find and browse available medicines in our inventory.</p>
                        <a href="search-medicines.php" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search Now
                        </a>
                    </div>
                </div>
            </div>

           

            <!-- My Profile -->
            <div class="col-md-4">
                <div class="card h-100 feature-card">
                    <div class="card-body text-center">
                        <i class="bi bi-person-circle icon-large text-info"></i>
                        <h5 class="card-title">My Profile</h5>
                        <p class="card-text">View and update your personal information.</p>
                        <a href="customer-profile.php" class="btn btn-info">
                            <i class="bi bi-person"></i> Manage Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prescription Submission Form -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Submit Prescription</h5>
                    </div>
                    <div class="card-body">
                        <form id="prescriptionForm" action="submit_prescription.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="prescription_file" class="form-label">Upload Prescription Image/Document</label>
                                <input type="file" class="form-control" id="prescription_file" name="prescription_file" 
                                    accept="image/*,.pdf" onchange="previewFile()" required>
                                <small class="text-muted">Accepted formats: Images (JPG, PNG) or PDF</small>
                                <div class="progress mt-2" id="uploadProgress" style="display: none;">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <div id="filePreview" class="mt-2" style="display: none;">
                                    <img id="previewImage" src="" alt="Preview" style="max-width: 100%; max-height: 300px;">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-file-earmark-medical"></i> Submit Prescription
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function previewFile() {
            const fileInput = document.getElementById('prescription_file');
            const previewImage = document.getElementById('previewImage');
            const filePreview = document.getElementById('filePreview');
            const uploadProgress = document.getElementById('uploadProgress');
            
            if (fileInput.files && fileInput.files[0]) {
                const file = fileInput.files[0];
                
                // Check if it's an image file
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        filePreview.style.display = 'block';
                    };
                    
                    reader.readAsDataURL(file);
                } else {
                    // For non-image files (like PDF), don't show preview
                    filePreview.style.display = 'none';
                }
            } else {
                filePreview.style.display = 'none';
            }
        }
        
        $(document).ready(function() {
            $('#prescriptionForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                $.ajax({
                    url: 'submit_prescription.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log("Submission response:", response);
                        
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                alert(result.message);
                                $('#prescriptionForm')[0].reset();
                                $('#filePreview').hide();
                            } else {
                                alert('Error: ' + result.message);
                            }
                        } catch (e) {
                            console.error('Invalid JSON response:', response, e);
                            if (response.includes('success')) {
                                alert('Prescription submitted successfully!');
                                $('#prescriptionForm')[0].reset();
                                $('#filePreview').hide();
                            } else {
                                alert('Error processing your request. Please try again.');
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error, xhr.responseText);
                        alert('Error submitting prescription. Please try again. Details: ' + error);
                    }
                });
            });
        });

        function searchMedicines() {
            const searchName = document.getElementById('searchName').value.trim();
            const medicinesTable = document.getElementById('medicinesTable').getElementsByTagName('tbody')[0];
            
            // Clear previous results
            medicinesTable.innerHTML = '';

            if (searchName === '') {
                alert('Please enter a medicine name to search.');
                return;
            }

            $.ajax({
                url: 'search_medicines.php', // Ensure this endpoint is set up to handle search queries
                type: 'GET',
                data: { name: searchName },
                success: function(response) {
                    try {
                        const results = JSON.parse(response);
                        if (results.length > 0) {
                            results.forEach((medicine, index) => {
                                const row = medicinesTable.insertRow();
                                row.innerHTML = `
                                    <td>${index + 1}</td>
                                    <td>${medicine.name}</td>
                                    <td>${medicine.stock_quantity}</td>
                                    <td>₹${medicine.price_per_unit.toFixed(2)}</td>
                                    <td>${medicine.company}</td>
                                    <td><span class="badge bg-${medicine.stock_quantity > 10 ? 'success' : (medicine.stock_quantity > 0 ? 'warning' : 'danger')}">${medicine.stock_quantity > 10 ? 'In Stock' : (medicine.stock_quantity > 0 ? 'Low Stock' : 'Out of Stock')}</span></td>
                                `;
                            });
                        } else {
                            medicinesTable.innerHTML = '<tr><td colspan="6" class="text-center">No medicines found.</td></tr>';
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        medicinesTable.innerHTML = '<tr><td colspan="6" class="text-center">Error processing search results.</td></tr>';
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    medicinesTable.innerHTML = '<tr><td colspan="6" class="text-center">Error fetching search results.</td></tr>';
                }
            });
        }
    </script>
</body>
</html>