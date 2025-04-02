<?php
// Only start session if one doesn't already exist
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include "connect.php";

function extractTextFromImage($imagePath) {
    // Check if file exists
    if (!file_exists($imagePath)) {
        error_log("File doesn't exist: " . $imagePath);
        return ["error" => "Image file not found"];
    }
    
    // Get file info and check if it's a valid image
    $imageInfo = getimagesize($imagePath);
    if (!$imageInfo) {
        error_log("Invalid image file: " . $imagePath);
        return ["error" => "Invalid image file"];
    }
    
    error_log("Image dimensions: " . $imageInfo[0] . "x" . $imageInfo[1] . ", type: " . $imageInfo[2]);
    
    // Check if image is too small or too large
    if ($imageInfo[0] < 100 || $imageInfo[1] < 100) {
        error_log("Image too small: " . $imageInfo[0] . "x" . $imageInfo[1]);
        return ["error" => "Image too small for accurate text extraction"];
    }
    
    // Pre-process the image if possible to improve OCR quality
    $processedImage = $imagePath;
    if (function_exists('imagecreatefromjpeg') && function_exists('imagejpeg')) {
        try {
            // Create new filename for processed image
            $processedImage = dirname($imagePath) . '/processed_' . basename($imagePath);
            
            // Load image based on type
            switch ($imageInfo[2]) {
                case IMAGETYPE_JPEG:
                    $image = imagecreatefromjpeg($imagePath);
                    break;
                case IMAGETYPE_PNG:
                    $image = imagecreatefrompng($imagePath);
                    break;
                default:
                    $image = false;
            }
            
            if ($image) {
                // Convert to grayscale to improve OCR
                imagefilter($image, IMG_FILTER_GRAYSCALE);
                // Increase contrast
                imagefilter($image, IMG_FILTER_CONTRAST, -10);
                // Sharpen image
                $sharpen = array(
                    array(0, -1, 0),
                    array(-1, 5, -1),
                    array(0, -1, 0)
                );
                imageconvolution($image, $sharpen, 1, 0);
                
                // Save processed image
                imagejpeg($image, $processedImage, 95);
                imagedestroy($image);
                
                error_log("Image pre-processed for better OCR: $processedImage");
            }
        } catch (Exception $e) {
            error_log("Image processing error: " . $e->getMessage());
            $processedImage = $imagePath; // Fallback to original
        }
    }
    
    // OPTION 1: Try using tesseract OCR locally if available
    if (function_exists('exec')) {
        $tesseractPath = 'tesseract';
        $outFile = dirname($processedImage) . '/' . pathinfo($processedImage, PATHINFO_FILENAME);
        
        // Use more advanced tesseract settings for better OCR
        $command = escapeshellcmd("$tesseractPath " . escapeshellarg($processedImage) . " " . 
                   escapeshellarg($outFile) . " -l eng --psm 6");
        
        exec($command, $output, $returnVar);
        
        if ($returnVar === 0) {
            $textFile = $outFile . '.txt';
            if (file_exists($textFile)) {
                $text = file_get_contents($textFile);
                unlink($textFile); // Clean up
                
                if (!empty(trim($text))) {
                    error_log("Successfully extracted text using local OCR");
                    
                    // Clean up processed image if it was created
                    if ($processedImage != $imagePath && file_exists($processedImage)) {
                        unlink($processedImage);
                    }
                    
                    // Clean up extracted text
                    $text = cleanExtractedText($text);
                    
                    return ["text" => $text];
                }
            }
        }
        
        error_log("Local OCR failed, falling back to API");
    }
    
    // OPTION 2: Try using the Gemini API
    $apiKey = 'AIzaSyBU2fqqYDZDUhe0CEPz2PLQHdZrWZ0d6IE';
    
    // Get MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $processedImage);
    finfo_close($finfo);
    
    // Check file size before base64 encoding
    $fileSize = filesize($processedImage);
    error_log("File size before encoding: " . $fileSize . " bytes");
    
    if ($fileSize > 10 * 1024 * 1024) { // 10MB
        error_log("Image too large for API: " . $fileSize . " bytes");
        
        // Try to resize the image
        if (function_exists('imagecreatefromjpeg')) {
            try {
                $resizedImage = dirname($processedImage) . '/resized_' . basename($processedImage);
                
                // Load image based on type
                switch ($imageInfo[2]) {
                    case IMAGETYPE_JPEG:
                        $image = imagecreatefromjpeg($processedImage);
                        break;
                    case IMAGETYPE_PNG:
                        $image = imagecreatefrompng($processedImage);
                        break;
                    default:
                        $image = false;
                }
                
                if ($image) {
                    // Get original dimensions
                    $origWidth = imagesx($image);
                    $origHeight = imagesy($image);
                    
                    // Calculate new dimensions to fit within 2MB when encoded
                    $ratio = min(1, sqrt(2 * 1024 * 1024 / $fileSize));
                    $newWidth = $origWidth * $ratio;
                    $newHeight = $origHeight * $ratio;
                    
                    // Create resized image
                    $resized = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                    
                    // Save resized image
                    imagejpeg($resized, $resizedImage, 80);
                    imagedestroy($image);
                    imagedestroy($resized);
                    
                    $processedImage = $resizedImage;
                    $mimeType = 'image/jpeg'; // Update MIME type
                    
                    error_log("Image resized for API: $resizedImage");
                }
            } catch (Exception $e) {
                error_log("Image resizing error: " . $e->getMessage());
            }
        }
    }
    
    // Convert image to base64
    $imageData = base64_encode(file_get_contents($processedImage));
    
    // Make API request
    $data = [
        "contents" => [
            [
                "parts" => [
                    [
                        "text" => "Extract the exact text visible in this image. Preserve the original case of the text. Do not add any additional line breaks, and do not include any interpretation or analysis."
                    ],
                    [
                        "inline_data" => [
                            "mime_type" => $mimeType,
                            "data" => $imageData
                        ]
                    ]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.1,
            "maxOutputTokens" => 1024
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-vision:generateContent?key=" . $apiKey);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Clean up processed images
    if ($processedImage != $imagePath && file_exists($processedImage)) {
        unlink($processedImage);
    }
    
    if ($curlError) {
        error_log("cURL Error: " . $curlError);
        return ["error" => "Connection error: " . $curlError];
    }
    
    if ($httpCode !== 200) {
        error_log("API Error (HTTP $httpCode): " . $response);
        return ["error" => "API error (HTTP $httpCode)"];
    }
    
    // Parse the response
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON Error: " . json_last_error_msg());
        return ["error" => "Failed to parse API response"];
    }
    
    // Extract text from the response
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $extractedText = $result['candidates'][0]['content']['parts'][0]['text'];
        error_log("Successfully extracted text from API");
        
        // Clean up extracted text
        $extractedText = cleanExtractedText($extractedText);
        
        return ["text" => $extractedText];
    }
    
    // FALLBACK OPTION: For images that couldn't be processed
    return ["text" => "The system couldn't fully read this prescription.\n\nPlease type the prescription details below:"];
}

// Add this new function to clean up extracted text
function cleanExtractedText($text) {
    // Trim whitespace
    $text = trim($text);
    
    // Remove extra newlines and carriage returns
    $text = str_replace(["\r\n", "\r", "\n\n"], "\n", $text);
    
    // Optional: Force all text to uppercase for handwritten notes
    // $text = strtoupper($text);
    
    return $text;
}

// Handle API calls for text extraction
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["prescription_file"])) {
    error_log("Received file upload request: " . $_FILES["prescription_file"]["name"]);
    
    // Create uploads directory
    $uploadDir = "uploads/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate a unique filename
    $tempFile = $uploadDir . uniqid() . "_" . basename($_FILES["prescription_file"]["name"]);
    
    // Move the uploaded file
    if (move_uploaded_file($_FILES["prescription_file"]["tmp_name"], $tempFile)) {
        error_log("File uploaded successfully to: " . $tempFile);
        
        // Process image files
        $fileExtension = strtolower(pathinfo($tempFile, PATHINFO_EXTENSION));
        if (in_array($fileExtension, ['jpg', 'jpeg', 'png'])) {
            // Extract text from the image
            $result = extractTextFromImage($tempFile);
            
            if (isset($result["text"])) {
                echo json_encode([
                    "success" => true,
                    "extractedText" => $result["text"]
                ]);
                
                // Save to database if needed
                if (isset($_SESSION['user_id'])) {
                    $userId = $_SESSION['user_id'];
                    $fileName = basename($_FILES["prescription_file"]["name"]);
                    $extractedText = $result["text"];
                    
                    try {
                        // Just store a reference to the file, not the whole binary
                        $stmt = $conn->prepare("INSERT INTO prescriptions 
                            (user_id, file_name, extracted_text, upload_date, status) 
                            VALUES (?, ?, ?, NOW(), 'pending')");
                        
                        if ($stmt) {
                            $stmt->bind_param("iss", $userId, $fileName, $extractedText);
                            $stmt->execute();
                            $stmt->close();
                        }
                    } catch (Exception $e) {
                        error_log("Database error: " . $e->getMessage());
                        // Continue processing even if database fails
                    }
                }
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Could not extract text: " . ($result["error"] ?? "Unknown error")
                ]);
            }
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Unsupported file type. Please upload JPG, JPEG, or PNG files."
            ]);
        }
        
        // Clean up
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to upload file"
        ]);
    }
    exit;
}
?> 