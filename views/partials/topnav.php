<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container-fluid">
        <!-- Sidebar Toggle -->
        <button class="btn btn-link sidebar-toggle d-lg-none">
            <i class="bi bi-list"></i>
        </button>
        
        <!-- Brand -->
        <span class="navbar-brand d-none d-lg-block">
            <?= APP_NAME ?>
        </span>
        
        <!-- Right Side -->
        <ul class="navbar-nav ms-auto">
            <!-- Notifications -->
            <li class="nav-item dropdown" id="notificationDropdown">
                <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-bell"></i>
                    <span class="badge bg-danger" id="notifCount" style="display:none;">0</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" style="min-width: 320px;" id="notifMenu">
                    <li><h6 class="dropdown-header">Notifications</h6></li>
                    <li><div class="text-center text-muted small p-2" id="notifEmpty">No notifications</div></li>
                </ul>
            </li>
            
            <!-- User Profile -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i>
                    <?= htmlspecialchars($user['full_name'] ?? 'User') ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">User Menu</h6></li>
                    <li><a class="dropdown-item" href="<?= APP_URL ?>/settings/profile"><i class="bi bi-person"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="<?= APP_URL ?>/settings/general"><i class="bi bi-gear"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= APP_URL ?>/logout"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav> 
<script>
// Load notifications for current user
document.addEventListener('DOMContentLoaded', function() {
    fetch(APP_URL + '/api/notifications/recent')
      .then(r => r.json())
      .then(resp => {
        if (!resp || !resp.success) return;
        const items = resp.data || [];
        const count = resp.unread ?? items.filter(i => i.is_read === '0' || i.is_read === 0).length;
        const countEl = document.getElementById('notifCount');
        const menu = document.getElementById('notifMenu');
        const empty = document.getElementById('notifEmpty');
        if (items.length > 0) {
          empty && empty.remove();
          items.forEach(n => {
            const li = document.createElement('li');
            li.innerHTML = `<a class="dropdown-item" href="#" data-id="${n.id}">${n.title}<br><small class="text-muted">${n.message}</small></a>`;
            menu.appendChild(li);
          });
          // Attach click handlers to open modal
          menu.querySelectorAll('a.dropdown-item').forEach(a => {
            a.addEventListener('click', (e) => {
              e.preventDefault();
              const id = a.getAttribute('data-id');
              fetch(APP_URL + '/api/notifications/view/' + id, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
                .then(async r => {
                  const text = await r.text();
                  try { return JSON.parse(text); } catch (e) { console.error('Notif view parse error:', text); throw e; }
                })
                .then(resp => {
                  if (!resp.success) return;
                  showNotificationModal(resp.transaction);
                });
            });
          });
        }
        if (count > 0) {
          countEl.textContent = count;
          countEl.style.display = '';
        }
      }).catch(() => {});
});
</script>
<!-- Notification Modal -->
<div class="modal fade" id="notifModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Transaction Review</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="notifTrnDetails" class="mb-3 small text-muted"></div>
        <div class="mb-3">
          <label class="form-label">Comment <span class="text-muted">(Required for rejection)</span></label>
          <textarea id="notifComment" class="form-control" rows="3" placeholder="Add a comment... (Required when rejecting)"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <!-- <button type="button" class="btn btn-primary" id="notifAddCommentBtn">Add Comment</button> -->
        <button type="button" class="btn btn-success" id="notifApproveBtn">Approve</button>
        <button type="button" class="btn btn-danger" id="notifRejectBtn">Reject</button>
      </div>
    </div>
  </div>
 </div>

<script>
let currentNotifTransactionId = null;

// Function to reload notification count
function reloadNotificationCount() {
  fetch(APP_URL + '/api/notifications/count')
    .then(r => r.json())
    .then(resp => {
      if (!resp || !resp.success) return;
      const count = resp.unread || 0;
      const countEl = document.getElementById('notifCount');
      if (count > 0) {
        countEl.textContent = count;
        countEl.style.display = '';
      } else {
        countEl.style.display = 'none';
      }
    }).catch(() => {});
}

// Function to reload notification list
function reloadNotificationList() {
  fetch(APP_URL + '/api/notifications/recent')
    .then(r => r.json())
    .then(resp => {
      if (!resp || !resp.success) return;
      const items = resp.data || [];
      const menu = document.getElementById('notifMenu');
      const empty = document.getElementById('notifEmpty');
      
      // Clear existing notifications
      const existingItems = menu.querySelectorAll('li:not(:first-child)');
      existingItems.forEach(item => item.remove());
      
      // Add empty message if no notifications
      if (items.length === 0) {
        if (!empty) {
          const emptyDiv = document.createElement('li');
          emptyDiv.innerHTML = '<div class="text-center text-muted small p-2" id="notifEmpty">No notifications</div>';
          menu.appendChild(emptyDiv);
        }
        return;
      }
      
      // Remove empty message if exists
      if (empty) {
        empty.remove();
      }
      
      // Add new notifications
      items.forEach(n => {
        const li = document.createElement('li');
        li.innerHTML = `<a class="dropdown-item" href="#" data-id="${n.id}">${n.title}<br><small class="text-muted">${n.message}</small></a>`;
        menu.appendChild(li);
      });
      
      // Re-attach click handlers to new notification items
      menu.querySelectorAll('a.dropdown-item').forEach(a => {
        a.addEventListener('click', (e) => {
          e.preventDefault();
          const id = a.getAttribute('data-id');
          fetch(APP_URL + '/api/notifications/view/' + id, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
            .then(async r => {
              const text = await r.text();
              try { return JSON.parse(text); } catch (e) { console.error('Notif view parse error:', text); throw e; }
            })
            .then(resp => {
              if (!resp.success) return;
              showNotificationModal(resp.transaction);
            });
        });
      });
    }).catch(() => {});
}

function showNotificationModal(trn){
  const details = document.getElementById('notifTrnDetails');
  if (trn) {
    currentNotifTransactionId = trn.id;
    const amount = (trn.amount || 0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
    
    // Generate distribution table if child transactions exist
    let distributionTable = '';
    if (trn.child_transactions && trn.child_transactions.length > 0) {
      const childTransactions = trn.child_transactions;
      let totalDebit = 0;
      let totalCredit = 0;
      
      const distributionRows = childTransactions.map(transaction => {
        // Determine transaction type from reference number if transaction_type is empty
        let transactionType = transaction.transaction_type;
        if (!transactionType || transactionType === '') {
          if (transaction.reference_no && transaction.reference_no.includes('-D')) {
            transactionType = 'debit';
          } else if (transaction.reference_no && transaction.reference_no.includes('-C')) {
            transactionType = 'credit';
          }
        }
        
        const amount = parseFloat(transaction.amount) || 0;
        if (transactionType === 'debit') totalDebit += amount;
        if (transactionType === 'credit') totalCredit += amount;
        
        return `
          <tr>
            <td>${transaction.account_name || 'N/A'}</td>
            <td>${transaction.project_name || '-'}</td>
            <td>${transaction.department_name || '-'}</td>
            <td>${transaction.supplier_name || '-'}</td>
            <td class="text-end">${transactionType === 'debit' ? '₱' + amount.toLocaleString('en-PH', {minimumFractionDigits: 2}) : '-'}</td>
            <td class="text-end">${transactionType === 'credit' ? '₱' + amount.toLocaleString('en-PH', {minimumFractionDigits: 2}) : '-'}</td>
          </tr>
        `;
      }).join('');
      
      distributionTable = `
        <hr class="my-3">
        <h6 class="fw-bold mb-2">Account Distribution</h6>
        <div class="table-responsive">
          <table class="table table-sm table-bordered">
            <thead class="table-light">
              <tr>
                <th>Account</th>
                <th>Project</th>
                <th>Department</th>
                <th>Subsidiary</th>
                <th>Debit</th>
                <th>Credit</th>
              </tr>
            </thead>
            <tbody>
              ${distributionRows}
            </tbody>
            <tfoot>
              <tr class="table-info">
                <td colspan="4" class="text-end"><strong>TOTAL:</strong></td>
                <td class="text-end"><strong>₱${totalDebit.toLocaleString('en-PH', {minimumFractionDigits: 2})}</strong></td>
                <td class="text-end"><strong>₱${totalCredit.toLocaleString('en-PH', {minimumFractionDigits: 2})}</strong></td>
              </tr>
            </tfoot>
          </table>
        </div>
      `;
    }
    
    details.innerHTML = `
      <div><strong>Type:</strong> ${trn.transaction_type}</div>
      <div><strong>Voucher:</strong> ${trn.reference_no}</div>
      <div><strong>Date:</strong> ${new Date(trn.transaction_date).toLocaleDateString()}</div>
      <div><strong>Payee:</strong> ${trn.payee_name || ''}</div>
      <div><strong>Amount:</strong> ₱${amount}</div>
      <hr class="my-2">
      <div class="text-muted">Distribution follows header decision on approval/rejection.</div>
      ${distributionTable}
    `;
  } else {
    details.textContent = 'No details available.';
  }
  const modal = new bootstrap.Modal(document.getElementById('notifModal'));
  modal.show();
}

document.addEventListener('click', function(e){
  if (e.target && e.target.id === 'notifAddCommentBtn') {
    const c = document.getElementById('notifComment').value.trim();
    if (!currentNotifTransactionId || !c) return;
    fetch(APP_URL + '/api/notifications/comment', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ transaction_id: currentNotifTransactionId, comment: c })
    }).then(r => r.json()).then(resp => {
      if (resp.success) { document.getElementById('notifComment').value = ''; }
    });
  }
  if (e.target && e.target.id === 'notifApproveBtn') {
    if (!currentNotifTransactionId) return;
    
    // Show confirmation dialog
    if (!confirm('Do you want to approve this transaction?')) {
      return;
    }
    
    fetch(APP_URL + '/api/transactions/' + currentNotifTransactionId + '/approve', { method: 'POST' })
      .then(r => r.json()).then(resp => {
        if (resp.success) {
          bootstrap.Modal.getInstance(document.getElementById('notifModal')).hide();
          // Show success message
          if (window.EARS && window.EARS.showAlert) {
            window.EARS.showAlert('Transaction approved successfully!', 'success');
          }
          // Reload notification count and list after a short delay
          setTimeout(() => {
            reloadNotificationCount();
            reloadNotificationList();
          }, 500);
        } else {
          // Show error message
          if (window.EARS && window.EARS.showAlert) {
            window.EARS.showAlert('Failed to approve transaction: ' + (resp.message || 'Unknown error'), 'danger');
          }
        }
      }).catch(error => {
        // Show error message
        if (window.EARS && window.EARS.showAlert) {
          window.EARS.showAlert('Failed to approve transaction: ' + error.message, 'danger');
        }
      });
  }
  if (e.target && e.target.id === 'notifRejectBtn') {
    if (!currentNotifTransactionId) return;
    const reason = document.getElementById('notifComment').value.trim();
    
    // Validate that a reason is provided for rejection
    if (!reason) {
      if (window.EARS && window.EARS.showAlert) {
        window.EARS.showAlert('Please provide a reason for rejection.', 'warning');
      }
      return;
    }
    
    // Show confirmation dialog
    if (!confirm('Do you want to reject this transaction?')) {
      return;
    }
    
    fetch(APP_URL + '/api/transactions/' + currentNotifTransactionId + '/reject', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ reason })
    }).then(r => r.json()).then(resp => {
      if (resp.success) {
        bootstrap.Modal.getInstance(document.getElementById('notifModal')).hide();
        // Show success message
        if (window.EARS && window.EARS.showAlert) {
          window.EARS.showAlert('Transaction rejected successfully!', 'success');
        }
        // Reload notification count and list after a short delay
        setTimeout(() => {
          reloadNotificationCount();
          reloadNotificationList();
        }, 500);
      } else {
        // Show error message
        if (window.EARS && window.EARS.showAlert) {
          window.EARS.showAlert('Failed to reject transaction: ' + (resp.message || 'Unknown error'), 'danger');
        }
      }
    }).catch(error => {
      // Show error message
      if (window.EARS && window.EARS.showAlert) {
        window.EARS.showAlert('Failed to reject transaction: ' + error.message, 'danger');
      }
    });
  }
});
</script>