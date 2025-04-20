<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upload PDF</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f5f0ff;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .upload-box {
      background-color: #e6e0f8;
      border: 2px dashed #b39ddb;
      border-radius: 12px;
      padding: 40px;
      text-align: center;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    .upload-box h2 { color: #6a1b9a; }
    input[type="file"] { padding: 10px; margin: 20px 0; }
    input[type="submit"] {
      background-color: #ba68c8;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
    input[type="submit"]:hover { background-color: #ab47bc; }
  </style>
</head>
<body>
  <form class="upload-box" action="upload.php" method="post" enctype="multipart/form-data">
    <h2>Insert Your PDF</h2>
    <input type="file" name="pdfFile" accept="application/pdf" required><br>
    <input type="submit" value="Upload PDF">
  </form>
</body>
</html>
