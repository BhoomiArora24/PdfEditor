<?php
$host = "localhost";
$dbname = "pdfFiles";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $dbname);

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT name, type, content FROM pdf_files WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($name, $type, $content);
        $stmt->fetch();

        header("Content-Type: $type");
        header("Content-Disposition: inline; filename=\"$name\"");
        echo $content;
    } else {
        echo "File not found.";
    }
    $stmt->close();
}
$conn->close();
?>
