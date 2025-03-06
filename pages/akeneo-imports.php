<div class="import-container">
  <div class="Box">
    <div class="Box-header">
      <h3 class="Box-title">Import and Process File</h3>
    </div>
    <div class="Box-body">

      <!-- File selection -->
      <div class="mb-3">
        <label for="fileInput" class="form-label">Select File</label>
        <input type="file" class="form-control" id="fileInput" name="file">
      </div>

      <!-- File Type Options -->
      <div class="mb-3">
        <span class="form-label">Choose File Type:</span>
        <div>
          <label class="form-check form-check-inline">
            <input type="radio" name="fileType" value="images" class="form-check-input" checked
              onclick="toggleDocOptions('images')">
            Images
          </label>
          <label class="form-check form-check-inline">
            <input type="radio" name="fileType" value="documents" class="form-check-input"
              onclick="toggleDocOptions('documents')">
            Documents
          </label>
        </div>
      </div>

      <!-- Document Source Options (only visible when Documents is selected) -->
      <div class="mb-3" id="docOptions" style="display:none;">
        <span class="form-label">Select Document Source:</span>
        <div>
          <label class="form-check form-check-inline">
            <input type="radio" name="docSource" value="aws" class="form-check-input">
            AWS
          </label>
          <label class="form-check form-check-inline">
            <input type="radio" name="docSource" value="external" class="form-check-input">
            External
          </label>
        </div>
      </div>

      <div class="mb-3">
        <span class="form-label">Replace existing values?</span>
        <div>
          <label class="form-check form-check-inline">
            <input type="radio" name="replaceMode" value="replace" class="form-check-input">
            Yes (Replace)
          </label>
          <label class="form-check form-check-inline">
            <input type="radio" name="replaceMode" value="append" class="form-check-input" checked>
            No (Append)
          </label>
        </div>
      </div>


      <!-- Button to start process -->
      <button type="button" class="btn btn-primary" onclick="startImport()">Process File</button>

      <!-- Progress bar & log box -->
      <div id="progress-container">
        <div id="progress-bar"></div>
      </div>
      <div id="log"></div>

    </div>
  </div>
</div>

<script src="/var/www/html/du/scripts/scripts.js"></script>