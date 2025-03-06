  <div class="container-lg">
    <h1>Akeneo Data Sync</h1>
    <p>Click a button to import data from Akeneo:</p>

    <button class="btn btn-primary sync-button" onclick="syncData('categories')">Sync Categories</button>
    <button class="btn btn-primary sync-button" onclick="syncData('families')">Sync Families</button>
    <button class="btn btn-primary sync-button" onclick="syncData('attributes')">Sync Attributes</button>
    <button class="btn btn-primary sync-button" onclick="syncData('values')">Sync Attribute Values</button>

    <h2>Sync Status</h2>
    <div id="syncResult" class="flash flash-warn" style="display:none;"></div>

    <h2>Sync History</h2>
    <table class="table" id="historyTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Entity</th>
          <th>Status</th>
          <th>Message</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

<script>

</script>

