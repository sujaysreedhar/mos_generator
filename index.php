<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Mosaic Poster Generator</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
:root {
  color-scheme: light;
  --bg: #f4f7fb;
  --surface: #ffffff;
  --surface-soft: #f8fafc;
  --text: #0f172a;
  --muted: #64748b;
  --border: #e2e8f0;
  --primary: #2563eb;
  --primary-dark: #1d4ed8;
  --danger: #dc2626;
  --success: #16a34a;
  --warning: #f59e0b;
  --shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
}

* { box-sizing: border-box; }
body {
  font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif;
  background: var(--bg);
  color: var(--text);
  margin: 0;
  padding: 28px;
}
h1, h2 { margin: 0; }
p { margin: 0; }

.page {
  max-width: 1100px;
  margin: 0 auto;
  display: grid;
  gap: 20px;
}

.hero {
  background: linear-gradient(120deg, #0f172a, #1e3a8a);
  color: #fff;
  padding: 28px;
  border-radius: 18px;
  box-shadow: var(--shadow);
}
.hero h1 { font-size: 28px; margin-bottom: 8px; }
.hero p { color: #dbeafe; font-size: 15px; }

form {
  background: var(--surface);
  padding: 24px;
  border-radius: 18px;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
}

.section {
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 16px;
  background: var(--surface-soft);
  margin-top: 16px;
}
.section h3 {
  font-size: 16px;
  margin-bottom: 8px;
}

label { display:block; margin-top:12px; font-weight:600; }
input, select, textarea, button {
  width:100%;
  padding:10px 12px;
  margin-top:6px;
  border-radius: 10px;
  border: 1px solid var(--border);
  font-size: 14px;
}
textarea { resize: vertical; min-height: 110px; }
input:focus, select:focus, textarea:focus {
  outline: 2px solid rgba(37, 99, 235, 0.2);
  border-color: var(--primary);
}

.fieldGrid {
  display: grid;
  gap: 14px;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

#dropzone {
  margin-top:10px;
  padding:32px;
  border:2px dashed #94a3b8;
  text-align:center;
  background:#fff;
  border-radius: 14px;
  cursor:pointer;
  transition: all 0.2s ease;
}
#dropzone.dragover {
  background:#e0e7ff;
  border-color: var(--primary);
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 12px;
}

button {
  background: var(--primary);
  color:#fff;
  border:none;
  cursor:pointer;
  font-size:15px;
  font-weight: 600;
  transition: transform 0.15s ease, background 0.15s ease;
}
button:hover { background: var(--primary-dark); }
button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}
.secondaryBtn {
  background: #0f172a;
}
.secondaryBtn:hover { background: #111827; }

#cancelBtn { background: var(--danger); }
#cancelBtn:hover { background: #b91c1c; }

#errors {
  color: var(--danger);
  margin-top:10px;
  white-space: pre-line;
}

.statRow {
  display: grid;
  gap: 10px;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  margin-top: 10px;
}
.statCard {
  padding: 12px;
  border-radius: 12px;
  background: #fff;
  border: 1px solid var(--border);
  font-size: 13px;
  color: var(--muted);
}
.statCard strong {
  display: block;
  color: var(--text);
  font-size: 15px;
  margin-top: 4px;
}

#previewGrid {
  margin-top:12px;
  display:grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap:10px;
}

.previewCard {
  border:1px solid var(--border);
  border-radius:10px;
  padding:6px;
  background:#fff;
}
.previewCard img {
  width:100%;
  height:90px;
  object-fit:cover;
  border-radius:6px;
}
.previewMeta {
  font-size:12px;
  margin-top:6px;
  color: var(--muted);
}
.previewRemove {
  margin-top:6px;
  width:100%;
  background: var(--danger);
  color:#fff;
  border:none;
  padding:6px;
  border-radius:8px;
  cursor:pointer;
}

#progressContainer {
  margin-top:20px;
  display:none;
  max-width: 1100px;
}
#progressBarOuter {
  width:100%;
  border:1px solid var(--border);
  height:28px;
  background:#fff;
  border-radius: 999px;
  overflow: hidden;
}
#progressBar {
  height:100%;
  width:0%;
  background: var(--success);
  color:#fff;
  text-align:center;
  line-height:28px;
  transition: width 0.3s;
  font-weight: 600;
}
#progressInfo { margin-top:10px; color: var(--muted); }
.smallNote { color: var(--muted); font-size: 12px; margin-top: 6px; }
.inlineNote { font-size: 13px; color: var(--muted); }

.pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 999px;
  background: #e2e8f0;
  font-size: 12px;
  color: var(--text);
}

.footerActions {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  margin-top: 18px;
}

@media (max-width: 720px) {
  body { padding: 18px; }
  .hero { padding: 20px; }
}
</style>
</head>

<body>

<div class="page">
  <div class="hero">
    <h1>Mosaic Poster Generator</h1>
    <p>Create modern, print-ready mosaic posters with live previews, smart validation, and faster uploads.</p>
  </div>

  <form id="mosaicForm">
    <div class="section">
      <h3>1) Upload your source images</h3>
      <div id="dropzone">Drop images here or click to select</div>

      <!-- IMPORTANT: accept image only -->
      <input type="file"
             name="images[]"
             id="images"
             multiple
             accept="image/*"
             hidden
             required>

      <div class="statRow" id="uploadStats">
        <div class="statCard">Selected Images<strong id="statsCount">0</strong></div>
        <div class="statCard">Total Size<strong id="statsSize">0 MB</strong></div>
        <div class="statCard">Previewing<strong id="statsPreview">0</strong></div>
      </div>

      <div class="toolbar">
        <button type="button" class="secondaryBtn" id="clearBtn">Clear All</button>
        <button type="button" class="secondaryBtn" id="togglePreviewBtn">Show All Previews</button>
      </div>

      <div class="smallNote" id="statsLine"></div>
      <div id="errors"></div>
      <div id="previewGrid"></div>
    </div>

    <div class="section">
      <h3>2) Text & layout</h3>
      <label>Text (multi-line supported)</label>
      <textarea name="text" rows="4" placeholder="ENTER YOUR TEXT" required></textarea>
      <div class="smallNote">Tip: Use line breaks to control the layout.</div>

      <div class="fieldGrid">
        <div>
          <label>Preset Size</label>
          <select id="presetSize">
            <option value="custom">Custom</option>
            <option value="20x10">20 ft × 10 ft</option>
            <option value="24x12">24 ft × 12 ft</option>
            <option value="36x18">36 ft × 18 ft</option>
          </select>
        </div>
        <div>
          <label>Poster Width (feet)</label>
          <input type="number" name="width_ft" value="20" min="1" step="0.1" required>
        </div>
        <div>
          <label>Poster Height (feet)</label>
          <input type="number" name="height_ft" value="10" min="1" step="0.1" required>
        </div>
      </div>
      <div class="smallNote">Maximum size is limited to 200,000,000 total pixels.</div>

      <div class="statRow">
        <div class="statCard">Estimated Resolution<strong id="statsResolution">0 × 0 px</strong></div>
        <div class="statCard">Pixel Area<strong id="statsPixelArea">0</strong></div>
        <div class="statCard">Performance Tip<strong id="statsPerf">Preview mode is faster.</strong></div>
      </div>
    </div>

    <div class="section">
      <h3>3) Output settings</h3>
      <div class="fieldGrid">
        <div>
          <label>Mode</label>
          <select name="mode" required>
            <option value="preview">Preview (Fast)</option>
            <option value="final">Final (Print)</option>
          </select>
          <div class="smallNote">Preview is recommended for quick checks.</div>
        </div>
        <div>
          <label>DPI (Final mode)</label>
          <input type="number" name="dpi" value="150" min="30" step="1">
          <div class="smallNote">Final mode clamps DPI to 30–600.</div>
        </div>
        <div>
          <label>Output Format</label>
          <select name="format" required>
            <option value="tiff">TIFF (Print)</option>
            <option value="pdf">PDF</option>
          </select>
        </div>
      </div>

      <div class="fieldGrid">
        <div>
          <label>Text Outline (px)</label>
          <input type="number" name="outline" value="12" min="0" step="1">
        </div>
        <div>
          <label>Text Glow Strength</label>
          <input type="number" name="glow" value="20" min="0" step="1">
        </div>
        <div>
          <label>Text Curve</label>
          <select name="curve" required>
            <option value="none">None</option>
            <option value="arc">Arc</option>
          </select>
        </div>
      </div>
    </div>

    <div class="footerActions">
      <button type="button" onclick="startGeneration()" id="generateBtn">Generate Mosaic</button>
      <button type="button" class="secondaryBtn" id="saveSettingsBtn">Save Settings</button>
      <button type="button" id="cancelBtn" onclick="cancelJob()" disabled>Cancel</button>
    </div>
  </form>

  <!-- PROGRESS -->
  <div id="progressContainer">
    <div id="progressBarOuter">
      <div id="progressBar">0%</div>
    </div>
    <div id="progressInfo">
      <p id="stageText"></p>
      <p id="etaText"></p>
      <p id="outputLink" class="inlineNote"></p>
    </div>
  </div>
</div>

<script>
const dropzone = document.getElementById("dropzone");
const fileInput = document.getElementById("images");
const previewGrid = document.getElementById("previewGrid");
const errorsBox = document.getElementById("errors");
const statsLine = document.getElementById("statsLine");
const form = document.getElementById("mosaicForm");
const statsCount = document.getElementById("statsCount");
const statsSize = document.getElementById("statsSize");
const statsPreview = document.getElementById("statsPreview");
const statsResolution = document.getElementById("statsResolution");
const statsPixelArea = document.getElementById("statsPixelArea");
const statsPerf = document.getElementById("statsPerf");
const clearBtn = document.getElementById("clearBtn");
const togglePreviewBtn = document.getElementById("togglePreviewBtn");
const generateBtn = document.getElementById("generateBtn");
const cancelBtn = document.getElementById("cancelBtn");
const presetSize = document.getElementById("presetSize");
const saveSettingsBtn = document.getElementById("saveSettingsBtn");
const outputLink = document.getElementById("outputLink");

// Holds chosen files across drag/drop + deletes
let selectedFiles = [];
let showAllPreviews = false;
let isGenerating = false;

// Client limits (adjust)
const MAX_FILES = 1500;
const MAX_TOTAL_MB = 500; // total upload size guard (client)
const MAX_PIXEL_AREA = 200000000;
const ALLOWED_PREFIX = "image/";
const MAX_PREVIEW = 60;
const SETTINGS_KEY = "mos_generator_settings";

function bytesToMB(b){ return b / (1024*1024); }

function showError(msg) {
  errorsBox.innerText = msg || "";
}

function updateStats() {
  const totalBytes = selectedFiles.reduce((s,f)=>s+f.size,0);
  const mb = bytesToMB(totalBytes).toFixed(1);
  const previewCount = Math.min(selectedFiles.length, showAllPreviews ? selectedFiles.length : MAX_PREVIEW);
  statsCount.innerText = selectedFiles.length;
  statsSize.innerText = `${mb} MB`;
  statsPreview.innerText = previewCount;
  statsLine.innerText = selectedFiles.length
    ? `${selectedFiles.length} image(s) selected • ~${mb} MB total`
    : "";
  togglePreviewBtn.innerText = showAllPreviews ? "Show Fewer Previews" : "Show All Previews";
  togglePreviewBtn.disabled = selectedFiles.length <= MAX_PREVIEW;
  clearBtn.disabled = selectedFiles.length === 0;
}

function rebuildFileInput() {
  const dt = new DataTransfer();
  selectedFiles.forEach(f => dt.items.add(f));
  fileInput.files = dt.files;
}

function renderPreviews() {
  previewGrid.innerHTML = "";
  const fragment = document.createDocumentFragment();
  const limit = showAllPreviews ? selectedFiles.length : MAX_PREVIEW;
  const visibleFiles = selectedFiles.slice(0, limit);

  visibleFiles.forEach((file, idx) => {
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
    fragment.appendChild(card);

    const url = URL.createObjectURL(file);
    img.src = url;
    img.onload = () => URL.revokeObjectURL(url);
  });

  previewGrid.appendChild(fragment);
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

function updateSizeStats() {
  const widthFt = parseFloat(form.elements.width_ft.value || "0");
  const heightFt = parseFloat(form.elements.height_ft.value || "0");
  const mode = form.elements.mode.value;
  const dpiInput = parseInt(form.elements.dpi.value || "0", 10);
  const dpi = mode === "preview" ? 30 : Math.min(600, Math.max(30, dpiInput || 150));
  const widthPx = Math.round(widthFt * 12 * dpi);
  const heightPx = Math.round(heightFt * 12 * dpi);
  const pixelArea = widthPx * heightPx;
  statsResolution.innerText = `${widthPx || 0} × ${heightPx || 0} px`;
  statsPixelArea.innerText = pixelArea ? pixelArea.toLocaleString() : "0";
  if (pixelArea > MAX_PIXEL_AREA) {
    statsPerf.innerText = "Size too large. Reduce dimensions or DPI.";
    statsPerf.style.color = "var(--danger)";
  } else if (mode === "preview") {
    statsPerf.innerText = "Preview mode is fastest for iteration.";
    statsPerf.style.color = "var(--muted)";
  } else {
    statsPerf.innerText = "Final mode may take longer and use more memory.";
    statsPerf.style.color = "var(--muted)";
  }
}

function setGeneratingState(active) {
  isGenerating = active;
  generateBtn.disabled = active;
  cancelBtn.disabled = !active;
  clearBtn.disabled = active || selectedFiles.length === 0;
  togglePreviewBtn.disabled = active || selectedFiles.length <= MAX_PREVIEW;
  saveSettingsBtn.disabled = active;
}

function applyPreset() {
  const value = presetSize.value;
  if (value === "custom") return;
  const [w, h] = value.split("x").map(Number);
  if (!Number.isNaN(w) && !Number.isNaN(h)) {
    form.elements.width_ft.value = w;
    form.elements.height_ft.value = h;
    updateSizeStats();
  }
}

/* START GENERATION */
function startGeneration() {
  if (!selectedFiles.length) {
    showError("Please select at least 1 image.");
    return;
  }

  const widthFt = parseFloat(form.elements.width_ft.value || "0");
  const heightFt = parseFloat(form.elements.height_ft.value || "0");
  const mode = form.elements.mode.value;
  const dpiInput = parseInt(form.elements.dpi.value || "0", 10);
  const dpi = mode === "preview" ? 30 : Math.min(600, Math.max(30, dpiInput || 150));
  const widthPx = Math.round(widthFt * 12 * dpi);
  const heightPx = Math.round(heightFt * 12 * dpi);
  const pixelArea = widthPx * heightPx;
  if (!widthPx || !heightPx || pixelArea > MAX_PIXEL_AREA) {
    showError("Poster size too large.");
    return;
  }

  showError("");

  document.getElementById("progressContainer").style.display = "block";
  outputLink.innerText = "";
  setGeneratingState(true);

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
          if (p.stage === "done" && p.message) {
            const match = p.message.match(/Output:\s*(\S+)/i);
            if (match) {
              const file = match[1];
              outputLink.innerHTML = `Output ready: <a href="output/${file}" target="_blank" rel="noopener">Download ${file}</a>`;
            }
          }
          setGeneratingState(false);
        }
      })
      .catch(() => {});
  }, 1000);
}

/* CANCEL */
function cancelJob() {
  fetch("cancel.php");
}

function clearAll() {
  selectedFiles = [];
  rebuildFileInput();
  renderPreviews();
  dropzone.innerText = "Drop images here or click to select";
  showError("");
}

function saveSettings() {
  const settings = {
    text: form.elements.text.value,
    width_ft: form.elements.width_ft.value,
    height_ft: form.elements.height_ft.value,
    mode: form.elements.mode.value,
    dpi: form.elements.dpi.value,
    format: form.elements.format.value,
    outline: form.elements.outline.value,
    glow: form.elements.glow.value,
    curve: form.elements.curve.value,
    presetSize: presetSize.value
  };
  localStorage.setItem(SETTINGS_KEY, JSON.stringify(settings));
  statsLine.innerText = "Settings saved locally.";
}

function loadSettings() {
  const raw = localStorage.getItem(SETTINGS_KEY);
  if (!raw) return;
  const settings = JSON.parse(raw);
  form.elements.text.value = settings.text || "";
  form.elements.width_ft.value = settings.width_ft || 20;
  form.elements.height_ft.value = settings.height_ft || 10;
  form.elements.mode.value = settings.mode || "preview";
  form.elements.dpi.value = settings.dpi || 150;
  form.elements.format.value = settings.format || "tiff";
  form.elements.outline.value = settings.outline || 12;
  form.elements.glow.value = settings.glow || 20;
  form.elements.curve.value = settings.curve || "none";
  presetSize.value = settings.presetSize || "custom";
}

togglePreviewBtn.addEventListener("click", () => {
  showAllPreviews = !showAllPreviews;
  renderPreviews();
});
clearBtn.addEventListener("click", clearAll);
presetSize.addEventListener("change", applyPreset);
saveSettingsBtn.addEventListener("click", saveSettings);
form.elements.mode.addEventListener("change", updateSizeStats);
form.elements.width_ft.addEventListener("input", updateSizeStats);
form.elements.height_ft.addEventListener("input", updateSizeStats);
form.elements.dpi.addEventListener("input", updateSizeStats);

loadSettings();
updateSizeStats();
updateStats();
setGeneratingState(false);
</script>

</body>
</html>
