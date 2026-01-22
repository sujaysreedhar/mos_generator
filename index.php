<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Mosaic Poster Generator</title>

<style>
body { font-family: Arial, sans-serif; background:#f5f5f5; padding:20px; }
h2 { margin-bottom: 10px; }
form { background:#fff; padding:20px; border-radius:6px; max-width: 980px; }
label { display:block; margin-top:12px; font-weight:bold; }
input, select, textarea, button { width:100%; padding:8px; margin-top:6px; }
textarea { resize: vertical; }

#dropzone {
  margin-top:10px; padding:40px; border:2px dashed #999;
  text-align:center; background:#fafafa; cursor:pointer;
}
#dropzone.dragover { background:#e0e0e0; }

button { margin-top:20px; background:#1976d2; color:#fff; border:none; cursor:pointer; font-size:16px; }
button:hover { background:#125aa0; }

#cancelBtn { background:#c62828; }
#cancelBtn:hover { background:#8e1e1e; }

#errors { color:#c62828; margin-top:10px; white-space: pre-line; }

#previewGrid {
  margin-top:12px;
  display:grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap:10px;
}

.previewCard {
  border:1px solid #ddd; border-radius:6px; padding:6px; background:#fff;
}
.previewCard img {
  width:100%; height:90px; object-fit:cover; border-radius:4px;
}
.previewMeta { font-size:12px; margin-top:6px; }
.previewRemove {
  margin-top:6px; width:100%; background:#c62828; color:#fff;
  border:none; padding:6px; border-radius:4px; cursor:pointer;
}

#progressContainer { margin-top:30px; display:none; max-width: 980px; }
#progressBarOuter { width:100%; border:1px solid #ccc; height:28px; background:#fff; }
#progressBar {
  height:100%; width:0%; background:#4caf50; color:#fff;
  text-align:center; line-height:28px; transition: width 0.3s;
}
#progressInfo { margin-top:10px; }
.smallNote { color:#555; font-size: 12px; margin-top: 6px; }
</style>
</head>

<body>

<h2>Mosaic Poster Generator</h2>

<form id="mosaicForm">
  <!-- IMAGE UPLOAD -->
  <label>Upload Images</label>
  <div id="dropzone">Drop images here or click to select</div>

  <!-- IMPORTANT: accept image only -->
  <input type="file"
         name="images[]"
         id="images"
         multiple
         accept="image/*"
         hidden
         required>

  <div class="smallNote" id="statsLine"></div>
  <div id="errors"></div>
  <div id="previewGrid"></div>

  <!-- TEXT -->
  <label>Text (multi-line supported)</label>
  <textarea name="text" rows="4" placeholder="ENTER YOUR TEXT" required></textarea>

  <!-- SIZE -->
  <label>Poster Width (feet)</label>
  <input type="number" name="width_ft" value="20" min="1" step="0.1" required>

  <label>Poster Height (feet)</label>
  <input type="number" name="height_ft" value="10" min="1" step="0.1" required>

  <!-- MODE -->
  <label>Mode</label>
  <select name="mode" required>
    <option value="preview">Preview (Fast)</option>
    <option value="final">Final (Print)</option>
  </select>

  <!-- DPI -->
  <label>DPI (Final mode)</label>
  <input type="number" name="dpi" value="150" min="30" step="1">

  <!-- OUTPUT -->
  <label>Output Format</label>
  <select name="format" required>
    <option value="tiff">TIFF (Print)</option>
    <option value="pdf">PDF</option>
  </select>

  <!-- TEXT STYLE -->
  <label>Text Outline (px)</label>
  <input type="number" name="outline" value="12" min="0" step="1">

  <label>Text Glow Strength</label>
  <input type="number" name="glow" value="20" min="0" step="1">

  <label>Text Curve</label>
  <select name="curve" required>
    <option value="none">None</option>
    <option value="arc">Arc</option>
  </select>

  <!-- ACTIONS -->
  <button type="button" onclick="startGeneration()">Generate Mosaic</button>
  <button type="button" id="cancelBtn" onclick="cancelJob()">Cancel</button>
</form>

<!-- PROGRESS -->
<div id="progressContainer">
  <div id="progressBarOuter">
    <div id="progressBar">0%</div>
  </div>
  <div id="progressInfo">
    <p id="stageText"></p>
    <p id="etaText"></p>
  </div>
</div>

<script>
const dropzone = document.getElementById("dropzone");
const fileInput = document.getElementById("images");
const previewGrid = document.getElementById("previewGrid");
const errorsBox = document.getElementById("errors");
const statsLine = document.getElementById("statsLine");
const form = document.getElementById("mosaicForm");

// Holds chosen files across drag/drop + deletes
let selectedFiles = [];

// Client limits (adjust)
const MAX_FILES = 1500;
const MAX_TOTAL_MB = 500; // total upload size guard (client)
const ALLOWED_PREFIX = "image/";

function bytesToMB(b){ return b / (1024*1024); }

function showError(msg) {
  errorsBox.innerText = msg || "";
}

function updateStats() {
  const totalBytes = selectedFiles.reduce((s,f)=>s+f.size,0);
  const mb = bytesToMB(totalBytes).toFixed(1);
  statsLine.innerText = selectedFiles.length
    ? `${selectedFiles.length} image(s) selected • ~${mb} MB total`
    : "";
}

function rebuildFileInput() {
  const dt = new DataTransfer();
  selectedFiles.forEach(f => dt.items.add(f));
  fileInput.files = dt.files;
}

function renderPreviews() {
  previewGrid.innerHTML = "";

  selectedFiles.forEach((file, idx) => {
    const card = document.createElement("div");
    card.className = "previewCard";

    const img = document.createElement("img");
    img.alt = file.name;

    const meta = document.createElement("div");
    meta.className = "previewMeta";
    meta.title = file.name;
    meta.innerText = file.name.length > 18 ? file.name.slice(0,18) + "…" : file.name;

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "previewRemove";
    btn.innerText = "Remove";
    btn.onclick = () => {
      selectedFiles.splice(idx, 1);
      rebuildFileInput();
      renderPreviews();
      dropzone.innerText = selectedFiles.length
        ? `${selectedFiles.length} images selected`
        : "Drop images here or click to select";
      showError("");
      updateStats();
    };

    card.appendChild(img);
    card.appendChild(meta);
    card.appendChild(btn);
    previewGrid.appendChild(card);

    const url = URL.createObjectURL(file);
    img.src = url;
    img.onload = () => URL.revokeObjectURL(url);
  });

  updateStats();
}

function validateFiles(files) {
  // images only
  for (const f of files) {
    if (!f.type || !f.type.startsWith(ALLOWED_PREFIX)) {
      return `Only image files are allowed.\n"${f.name}" is not an image.`;
    }
  }

  // count
  if (selectedFiles.length + files.length > MAX_FILES) {
    return `Too many files. Max allowed is ${MAX_FILES}.`;
  }

  // total size
  const totalBytes = [...selectedFiles, ...files].reduce((s,f)=>s+f.size,0);
  if (bytesToMB(totalBytes) > MAX_TOTAL_MB) {
    return `Total upload too large (>${MAX_TOTAL_MB} MB).\nRemove some files or lower selection.`;
  }

  return "";
}

function addFiles(files) {
  const arr = Array.from(files);

  const err = validateFiles(arr);
  if (err) { showError(err); return; }

  // de-dupe by name+size+lastModified
  const key = f => `${f.name}_${f.size}_${f.lastModified}`;
  const existing = new Set(selectedFiles.map(key));
  for (const f of arr) {
    if (!existing.has(key(f))) selectedFiles.push(f);
  }

  rebuildFileInput();
  renderPreviews();
  dropzone.innerText = `${selectedFiles.length} images selected`;
  showError("");
}

/* CLICK + DRAG/DROP */
dropzone.onclick = () => fileInput.click();

fileInput.addEventListener("change", () => {
  addFiles(fileInput.files);
  fileInput.value = ""; // allow re-pick same file
});

dropzone.ondragover = e => {
  e.preventDefault();
  dropzone.classList.add("dragover");
};
dropzone.ondragleave = () => dropzone.classList.remove("dragover");
dropzone.ondrop = e => {
  e.preventDefault();
  dropzone.classList.remove("dragover");
  addFiles(e.dataTransfer.files);
};

/* START GENERATION */
function startGeneration() {
  if (!selectedFiles.length) {
    showError("Please select at least 1 image.");
    return;
  }
  showError("");

  document.getElementById("progressContainer").style.display = "block";

  const data = new FormData(form);

  // IMPORTANT: fetch is fire-and-forget; polling reads progress.json
  fetch("generate.php", { method: "POST", body: data });

  pollProgress();
}

/* POLL PROGRESS */
function pollProgress() {
  const interval = setInterval(() => {
    fetch("progress.php", { cache: "no-store" })
      .then(r => r.json())
      .then(p => {
        if (!p || !p.stage) return;

        const percent = p.total
          ? Math.round((p.current / p.total) * 100)
          : 0;

        const bar = document.getElementById("progressBar");
        bar.style.width = percent + "%";
        bar.innerText = percent + "%";

        document.getElementById("stageText").innerText =
          "Stage: " + p.stage + (p.message ? " – " + p.message : "");

        document.getElementById("etaText").innerText =
          p.eta ? "ETA: " + p.eta : "";

        if (p.stage === "done" || p.stage === "cancelled" || p.stage === "error") {
          clearInterval(interval);
          if (p.stage === "error" && p.message) showError(p.message);
        }
      })
      .catch(() => {});
  }, 1000);
}

/* CANCEL */
function cancelJob() {
  fetch("cancel.php");
}
</script>

</body>
</html>
