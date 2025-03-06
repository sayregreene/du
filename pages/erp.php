  <div class="container-lg p-4">
    <!-- Header -->
    <header class="mb-4">
      <h1>SKU Update Dashboard</h1>
    </header>

    <!-- Filters -->
    <div class="mb-4">
      <h2>Filters</h2>
      <div class="d-flex flex-items-center">
        <select class="form-select mr-2">
          <option>Last 7 Days</option>
          <option>Last 30 Days</option>
          <option>Last 90 Days</option>
        </select>
        <select class="form-select">
          <option>All Manufacturers</option>
          <option>Manufacturer A</option>
          <option>Manufacturer B</option>
          <option>Manufacturer C</option>
        </select>
      </div>
    </div>

    <!-- Key Metrics -->
    <div class="d-flex flex-wrap mb-4">
      <div class="col-12 col-md-4 p-2">
        <div class="Box p-3">
          <p class="text-gray mb-1">Total SKUs Updated</p>
          <p class="h2">1,234</p>
        </div>
      </div>
      <div class="col-12 col-md-4 p-2">
        <div class="Box p-3">
          <p class="text-gray mb-1">Most Common Update Type</p>
          <p class="h2">Price Update</p>
        </div>
      </div>
      <div class="col-12 col-md-4 p-2">
        <div class="Box p-3">
          <p class="text-gray mb-1">Top Manufacturer</p>
          <p class="h2">Manufacturer A</p>
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="mb-4">
      <h2>Breakdown by Type of Update</h2>
      <div class="Box p-3">
        <canvas id="updateTypeChart"></canvas>
      </div>
    </div>
    <div class="mb-4">
      <h2>Distribution by Manufacturer</h2>
      <div class="Box p-3">
        <canvas id="manufacturerChart"></canvas>
      </div>
    </div>

    <!-- Insights -->
    <div class="Box p-3">
      <h3>Insights</h3>
      <ul>
        <li>Price updates accounted for <strong>40%</strong> of all updates.</li>
        <li>Manufacturer A had the highest number of updates (<strong>400 SKUs</strong>).</li>
        <li>Inventory updates increased by <strong>15%</strong> compared to last month.</li>
      </ul>
    </div>
  </div>

  <script>



  </script>