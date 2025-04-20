<?php
$host = "localhost";
$dbname = "pdfFiles";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['pdfFile']) && $_FILES['pdfFile']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['pdfFile']['tmp_name'];
        $fileName = $_FILES['pdfFile']['name'];
        $fileSize = $_FILES['pdfFile']['size'];
        $fileType = $_FILES['pdfFile']['type'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExt !== 'pdf') {
            die("Only PDF files are allowed.");
        }

        $fileContent = file_get_contents($fileTmpPath);

        $stmt = $conn->prepare("INSERT INTO pdf_files (name, type, size, content) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $fileName, $fileType, $fileSize, $fileContent);
        $stmt->execute();

        $pdfId = $stmt->insert_id;
        $stmt->close();
        $conn->close();

        // Redirect to editor
        header("Location: editor.php?id=$pdfId");
        exit;
    } else {
        echo "Upload error.";
    }
}
?>
