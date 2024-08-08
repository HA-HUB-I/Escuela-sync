jQuery(document).ready(function($) {
  $('#start-sync').click(function() {
      var batch = 0;
      var totalProducts = 0;

      function processBatch(batch) {
          $.ajax({
              url: escuelaStoreSync.ajax_url,
              type: 'post',
              data: {
                  action: 'escuela_store_sync',
                  nonce: escuelaStoreSync.nonce,
                  batch: batch
              },
              success: function(response) {
                  if (response.success) {
                      if (totalProducts === 0) {
                          totalProducts = response.data.total_products;
                          $('#progress-wrap').show();
                      }

                      var progress = Math.min(100, ((batch + 1) * 5 / totalProducts) * 100);
                      $('#progress-bar').val(progress);
                      $('#progress-count').text((batch + 1) * 5);

                      if (response.data.next_batch !== -1) {
                          processBatch(response.data.next_batch);
                      } else {
                          $('#sync-status').text('Синхронизацията приключи успешно.');
                      }
                  } else {
                      $('#sync-status').text('Грешка при синхронизация: ' + response.data.message);
                  }
              },
              error: function() {
                  $('#sync-status').text('Грешка при синхронизацията.');
              }
          });
      }

      $('#sync-status').text('Започва синхронизация...');
      processBatch(batch);
  });
});
