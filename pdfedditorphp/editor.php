<?php $pdfId = $_GET['id']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PDF Editor</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <style>
    body {
      font-family: sans-serif;
      background: lavender;
      text-align: center;
      margin: 0;
    }
    canvas {
      border: 1px solid #aaa;
      margin-top: 20px;
      cursor: crosshair;
    }
    #controls {
      margin: 20px;
    }
    #textInput, #fontSelect {
      padding: 8px;
      width: 200px;
    }
    button {
      padding: 8px 12px;
      margin-left: 10px;
      background-color: #ba68c8;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    button:hover {
      background-color: #ab47bc;
    }
    #fontControls {
      position: absolute;
      top: 10px;
      right: 10px;
    }
    #fontControls button {
      margin-left: 5px;
    }
  </style>
</head>
<body>

  <div id="controls">
    <input type="text" id="textInput" placeholder="Enter text to add" />
    <select id="fontSelect">
      <option value="Arial">Arial</option>
      <option value="Courier New">Courier New</option>
      <option value="Georgia">Georgia</option>
      <option value="Times New Roman">Times New Roman</option>
      <option value="Verdana">Verdana</option>
    </select>
    <button onclick="toggleBold()">Bold</button>
    <button onclick="toggleItalic()">Italic</button>
    <button onclick="downloadPDF()">Download as PDF</button>
  </div>

  <div id="fontControls">
    <button onclick="changeFontSize(2)">A+</button>
    <button onclick="changeFontSize(-2)">A-</button>
    <button onclick="undo()">Undo</button>
  </div>

  <canvas id="pdf-canvas"></canvas>

  <script>
    const canvas = document.getElementById('pdf-canvas');
    const ctx = canvas.getContext('2d');
    const url = "get_pdf.php?id=<?= $pdfId ?>";

    let viewport, pdfImage;
    let textItems = [];
    let draggingText = null;
    let selectedText = null;
    let offsetX = 0, offsetY = 0;
    let wasDragging = false;
    let defaultFontSize = 20;
    let history = [];

    pdfjsLib.getDocument(url).promise.then(pdf => pdf.getPage(1)).then(page => {
      viewport = page.getViewport({ scale: 1.5 });
      canvas.width = viewport.width;
      canvas.height = viewport.height;
      return page.render({ canvasContext: ctx, viewport }).promise;
    }).then(() => {
      pdfImage = new Image();
      pdfImage.src = canvas.toDataURL("image/png");
      pdfImage.onload = () => renderCanvas();
    });

    function renderCanvas(showSelection = true) {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      if (pdfImage) ctx.drawImage(pdfImage, 0, 0, canvas.width, canvas.height);

      textItems.forEach(item => {
        const fontStyle = `${item.fontStyle || ''} ${item.fontWeight || ''} ${item.fontSize}px ${item.font || 'Arial'}`;
        ctx.font = fontStyle.trim();
        ctx.fillStyle = "black";
        ctx.fillText(item.text, item.x, item.y);

        if (showSelection && item === selectedText) {
          const width = ctx.measureText(item.text).width;
          const height = item.fontSize;
          ctx.strokeStyle = "#673ab7";
          ctx.lineWidth = 1;
          ctx.strokeRect(item.x - 2, item.y - height, width + 4, height + 4);
        }
      });

      if (selectedText) {
        document.getElementById("fontSelect").value = selectedText.font;
      }
    }

    function getTextAt(x, y) {
      return textItems.find(item => {
        const fontStyle = `${item.fontStyle || ''} ${item.fontWeight || ''} ${item.fontSize}px ${item.font || 'Arial'}`;
        ctx.font = fontStyle.trim();
        const width = ctx.measureText(item.text).width;
        const height = item.fontSize;
        return x >= item.x && x <= item.x + width && y <= item.y && y >= item.y - height;
      });
    }

    canvas.addEventListener("mousedown", e => {
      const rect = canvas.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      const selected = getTextAt(x, y);

      if (selected) {
        draggingText = selected;
        selectedText = selected;
        offsetX = x - selected.x;
        offsetY = y - selected.y;
        wasDragging = false;
        canvas.style.cursor = "grabbing";
      } else {
        selectedText = null;
      }

      renderCanvas();
    });

    canvas.addEventListener("mousemove", e => {
      if (draggingText) {
        wasDragging = true;
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        draggingText.x = x - offsetX;
        draggingText.y = y - offsetY;
        renderCanvas();
      }
    });

    canvas.addEventListener("mouseup", () => {
      if (draggingText && wasDragging) {
        history.push({
          type: 'move',
          item: draggingText,
          prevX: draggingText.x + offsetX,
          prevY: draggingText.y + offsetY
        });
      }
      draggingText = null;
      canvas.style.cursor = "default";
    });

    canvas.addEventListener("click", e => {
      if (wasDragging) {
        wasDragging = false;
        return;
      }

      const rect = canvas.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;

      const clicked = getTextAt(x, y);
      if (clicked) {
        selectedText = clicked;
      } else {
        const text = document.getElementById("textInput").value;
        const font = document.getElementById("fontSelect").value;
        if (text.trim() !== "") {
          const newText = {
            text,
            x,
            y,
            fontSize: defaultFontSize,
            font,
            fontWeight: 'normal',
            fontStyle: 'normal'
          };
          textItems.push(newText);
          selectedText = newText;
          history.push({ type: 'add', item: newText });
        }
      }

      renderCanvas();
    });

    function changeFontSize(amount) {
      if (selectedText) {
        selectedText.fontSize = Math.max(8, selectedText.fontSize + amount);
        renderCanvas();
      }
    }

    function undo() {
      if (history.length === 0) return;

      const lastAction = history.pop();
      if (lastAction.type === 'add') {
        const index = textItems.indexOf(lastAction.item);
        if (index > -1) {
          textItems.splice(index, 1);
          if (selectedText === lastAction.item) selectedText = null;
        }
      } else if (lastAction.type === 'move') {
        lastAction.item.x = lastAction.prevX - offsetX;
        lastAction.item.y = lastAction.prevY - offsetY;
      }

      renderCanvas();
    }

    document.getElementById("fontSelect").addEventListener("change", () => {
      if (selectedText) {
        selectedText.font = document.getElementById("fontSelect").value;
        renderCanvas();
      }
    });

    function toggleBold() {
      if (selectedText) {
        selectedText.fontWeight = selectedText.fontWeight === 'bold' ? 'normal' : 'bold';
        renderCanvas();
      }
    }

    function toggleItalic() {
      if (selectedText) {
        selectedText.fontStyle = selectedText.fontStyle === 'italic' ? 'normal' : 'italic';
        renderCanvas();
      }
    }

    async function downloadPDF() {
      const { jsPDF } = window.jspdf;

      renderCanvas(false); // hide selection box

      const pdf = await pdfjsLib.getDocument(url).promise;
      const page = await pdf.getPage(1);
      const originalViewport = page.getViewport({ scale: 1 });
      const originalWidth = originalViewport.width;
      const originalHeight = originalViewport.height;

      const doc = new jsPDF({
        orientation: originalWidth > originalHeight ? 'landscape' : 'portrait',
        unit: 'pt',
        format: [originalWidth, originalHeight]
      });

      const imgData = canvas.toDataURL("image/png");
      doc.addImage(imgData, 'PNG', 0, 0, originalWidth, originalHeight);

      const match = url.match(/([^\/?&=]+\.pdf)/i);
      const originalName = match ? match[1] : "document.pdf";
      const editedName = "edited_" + originalName;

      doc.save(editedName);

      renderCanvas(true); // restore selection box
    }
  </script>
</body>
</html>
