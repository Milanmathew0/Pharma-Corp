<?php
// Define upload directory and allowed file type
$targetDir = "uploads/";
$allowedType = "application/pdf";
$maxSize = 5 * 1024 * 1024; // 5MB limit

// Create uploads directory if it doesn't exist
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// Initialize variables
$message = "";
$extractedText = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if file was uploaded without errors
    if (isset($_FILES["pdfFile"]) && $_FILES["pdfFile"]["error"] == 0) {
        $fileName = basename($_FILES["pdfFile"]["name"]);
        $fileSize = $_FILES["pdfFile"]["size"];
        $fileType = $_FILES["pdfFile"]["type"];
        $targetFilePath = $targetDir . $fileName;
        
        // Verify file type is PDF
        if ($fileType == $allowedType || pathinfo($fileName, PATHINFO_EXTENSION) == 'pdf') {
            // Check file size
            if ($fileSize <= $maxSize) {
                // Try to upload file
                if (move_uploaded_file($_FILES["pdfFile"]["tmp_name"], $targetFilePath)) {
                    $message = "The PDF file " . $fileName . " has been uploaded successfully.";
                    
                    // Extract text from the PDF using pdftotext (requires poppler-utils)
                    // Method 1: Using pdftotext if available on server
                    if (function_exists('exec')) {
                        $command = "pdftotext " . escapeshellarg($targetFilePath) . " -";
                        exec($command, $output, $returnVar);
                        if ($returnVar == 0) {
                            $extractedText = implode("\n", $output);
                        }
                    }
                    
                    // Method 2: Using PHP PDF Parser library
                    // Note: You need to install this library using Composer:
                    // composer require smalot/pdfparser
                    if (empty($extractedText) && file_exists('vendor/autoload.php')) {
                        require_once 'vendor/autoload.php';
                        try {
                            $parser = new \Smalot\PdfParser\Parser();
                            $pdf = $parser->parseFile($targetFilePath);
                            $extractedText = $pdf->getText();
                        } catch (Exception $e) {
                            $extractedText = "Error extracting text: " . $e->getMessage();
                        }
                    }
                    
                    // Method 3: Using built-in PHP functions if available
                    if (empty($extractedText) && extension_loaded('pdflib')) {
                        // Placeholder for PDFlib implementation
                        $extractedText = "PDFlib extension detected, but implementation is not included in this example.";
                    }
                    
                    // Fallback message if no extraction method is available
                    if (empty($extractedText)) {
                        $extractedText = "No PDF text extraction method is available on this server. Please install pdftotext (poppler-utils) or the PHP PDF Parser library (composer require smalot/pdfparser).";
                    }
                    
                } else {
                    $message = "Sorry, there was an error uploading your file.";
                }
            } else {
                $message = "Sorry, your file is too large. Maximum size is 5MB.";
            }
        } else {
            $message = "Sorry, only PDF files are allowed.";
        }
    } else if (isset($_FILES["pdfFile"])) {
        $message = "Error: " . $_FILES["pdfFile"]["error"];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Upload and Text Extraction - Pharma Corp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c9db7;
            --secondary-color: #858796;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(rgba(44, 157, 183, 0.1), rgba(44, 157, 183, 0.1)),
                        url('pic/3.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding: 20px 0;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        h1, h2 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .form-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .submit-btn {
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: #248ca3;
            transform: translateY(-2px);
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .text-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            font-family: monospace;
            line-height: 1.5;
        }

        label {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 8px;
        }

        input[type="file"] {
            border: 2px solid #e3e6f0;
            border-radius: 10px;
            padding: 8px;
            width: 100%;
        }

        textarea {
            border: 2px solid #e3e6f0;
            border-radius: 10px;
            padding: 10px;
            width: 100%;
        }

        textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(44, 157, 183, 0.25);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h1>PDF Upload and Text Extraction</h1>
                <p class="text-muted">Upload and extract text from prescription PDFs</p>
            </div>
            <div class="col-auto">
                <a href="staff-dashboard.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo (strpos($message, "successfully") !== false) ? "success" : "error"; ?>">
                <i class="bi <?php echo (strpos($message, "successfully") !== false) ? "bi-check-circle" : "bi-exclamation-circle"; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="pdfFile">Select a PDF file to upload:</label>
                    <input type="file" name="pdfFile" id="pdfFile" accept="application/pdf" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description (optional):</label>
                    <textarea name="description" id="description" rows="4" cols="50"></textarea>
                </div>
                
                <input type="submit" value="Upload PDF and Extract Text" class="submit-btn">
            </form>
        </div>
        
        <?php if (!empty($extractedText)): ?>
            <h2>Extracted Text</h2>
            <div class="text-container">
                <?php echo htmlspecialchars($extractedText); ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>