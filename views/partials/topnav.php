<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container-fluid">
        <!-- Sidebar Toggle -->
        <button class="btn btn-link sidebar-toggle d-lg-none">
            <i class="bi bi-list"></i>
        </button>
        
        <!-- Brand -->
        <span class="navbar-brand  d-lg-block">
            <!-- <?= APP_NAME ?> -->
            <i class="bi bi-graph-up"></i>
            EARS
        </span>
        
        <!-- Right Side -->
        <ul class="navbar-nav ms-auto">
            <!-- Notifications -->
            <li class="nav-item dropdown" id="notificationDropdown">
                <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-bell"></i>
                    <span class="badge bg-danger" id="notifCount" style="display:none;">0</span>
                </a>
                                 <ul class="dropdown-menu dropdown-menu-end" style="min-width: 320px; max-height: 400px; overflow-y: auto;" id="notifMenu">
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
        
        // Filter to only show unread notifications
        const unreadItems = items.filter(i => i.is_read === '0' || i.is_read === 0);
        
        if (unreadItems.length > 0) {
          empty && empty.remove();
          unreadItems.forEach(n => {
            const li = document.createElement('li');
            li.innerHTML = `<a class="dropdown-item" href="#" data-id="${n.id}">${n.title}<br><small class="text-muted">${n.message}</small></a>`;
            menu.appendChild(li);
          });
          // Attach click handlers to open modal
          menu.querySelectorAll('a.dropdown-item').forEach(a => {
            a.addEventListener('click', (e) => {
              e.preventDefault();
              const id = a.getAttribute('data-id');
              currentNotificationId = id; // Set the current notification ID
              console.log('Notification clicked, setting currentNotificationId to:', id);
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
        } else {
          // Show empty message if no unread notifications
          if (!empty) {
            const emptyDiv = document.createElement('li');
            emptyDiv.innerHTML = '<div class="text-center text-muted small p-2" id="notifEmpty">No notifications</div>';
            menu.appendChild(emptyDiv);
          }
        }
        if (count > 0) {
          countEl.textContent = count;
          countEl.style.display = '';
        } else {
          countEl.style.display = 'none';
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
let currentNotificationId = null; // Add this line to track the current notification ID
let currentTransactionReference = null; // Add this line to track the current transaction reference number
let currentTransactionType = null; // Add this line to store the transaction type

// Custom alert function with guaranteed auto-dismiss
function showAutoDismissAlert(message, type = 'info') {
  // Create alert element
  const alertElement = document.createElement('div');
  alertElement.className = `alert alert-${type} alert-dismissible fade show shadow`;
  alertElement.setAttribute('role', 'alert');
  alertElement.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  `;
  
  // Find or create container
  let container = document.getElementById('globalAlertContainer');
  if (!container) {
    container = document.getElementById('alertContainer');
  }
  if (!container) {
    container = document.createElement('div');
    container.id = 'globalAlertContainer';
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.left = '50%';
    container.style.transform = 'translateX(-50%)';
    container.style.zIndex = '9999';
    container.style.maxWidth = '600px';
    container.style.width = '90%';
    document.body.appendChild(container);
  }
  
  // Clear existing alerts and add new one
  container.innerHTML = '';
  container.appendChild(alertElement);
  
  // Scroll to top
  try {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch (e) {
    window.scrollTo(0, 0);
  }
  
  // Auto-dismiss after 2.5 seconds with fade out effect
  const dismissTimeout = setTimeout(function() {
    dismissAlert(alertElement);
  }, 2500);
  
  // Handle manual close button click
  const closeButton = alertElement.querySelector('.btn-close');
  if (closeButton) {
    // Remove any existing event listeners to prevent duplication
    const newCloseButton = closeButton.cloneNode(true);
    closeButton.parentNode.replaceChild(newCloseButton, closeButton);
    
    newCloseButton.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      clearTimeout(dismissTimeout);
      dismissAlert(alertElement);
    });
  }
  
  // Helper function to dismiss alert
  function dismissAlert(element) {
    if (element && element.parentNode) {
      element.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
      element.style.opacity = '0';
      element.style.transform = 'translateY(-10px)';
      setTimeout(function() {
        if (element && element.parentNode) {
          element.parentNode.removeChild(element);
        }
      }, 300);
    }
  }
}

// Function to reload notification count
function reloadNotificationCount() {
  console.log('Reloading notification count...');
  fetch(APP_URL + '/api/notifications/count')
    .then(r => r.json())
    .then(resp => {
      console.log('Notification count response:', resp);
      if (!resp || !resp.success) return;
      const count = resp.unread || 0;
      console.log('New notification count:', count);
      const countEl = document.getElementById('notifCount');
      if (count > 0) {
        countEl.textContent = count;
        countEl.style.display = '';
      } else {
        countEl.style.display = 'none';
      }
    }).catch((error) => {
      console.error('Error reloading notification count:', error);
    });
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
      
      // Filter to only show unread notifications
      const unreadItems = items.filter(i => i.is_read === '0' || i.is_read === 0);
      
      // Add empty message if no unread notifications
      if (unreadItems.length === 0) {
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
      
      // Add new unread notifications
      unreadItems.forEach(n => {
        const li = document.createElement('li');
        li.innerHTML = `<a class="dropdown-item" href="#" data-id="${n.id}">${n.title}<br><small class="text-muted">${n.message}</small></a>`;
        menu.appendChild(li);
      });
      
      // Re-attach click handlers to new notification items
      menu.querySelectorAll('a.dropdown-item').forEach(a => {
        a.addEventListener('click', (e) => {
          e.preventDefault();
          const id = a.getAttribute('data-id');
          currentNotificationId = id; // Store the notification ID
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
  console.log('Showing notification modal for transaction:', trn);
  
  const details = document.getElementById('notifTrnDetails');
  if (trn) {
    currentNotifTransactionId = trn.id;
    currentTransactionReference = trn.reference_no; // Store the reference number
    currentTransactionType = trn.transaction_type; // Store the transaction type
    console.log('Set currentNotifTransactionId to:', currentNotifTransactionId);
    console.log('Set currentTransactionType to:', currentTransactionType);
    console.log('Transaction data received:', JSON.stringify(trn, null, 2));
    
    const amount = (trn.amount || trn.total_amount || 0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
    
    // Format transaction type for display
    let transactionType = 'Unknown';
    switch(trn.transaction_type) {
      case 'cash_receipt':
        transactionType = 'Cash Receipt';
        break;
      case 'cash_disbursement':
        transactionType = 'Cash Disbursement';
        break;
      case 'check_disbursement':
        transactionType = 'Check Disbursement';
        break;
      default:
        transactionType = trn.transaction_type || 'Unknown';
        break;
    }
    
    console.log('Transaction type determined:', transactionType);
    console.log('Amount:', amount);
    
    // Generate distribution table if distributions exist
    let distributionTable = '';
    if (trn.distributions && trn.distributions.length > 0) {
      console.log('Distributions found:', trn.distributions);
      const distributions = trn.distributions;
      let totalDebit = 0;
      let totalCredit = 0;
      
      const distributionRows = distributions.map(distribution => {
        const amount = parseFloat(distribution.amount) || 0;
        if (distribution.payment_type === 'debit') totalDebit += amount;
        if (distribution.payment_type === 'credit') totalCredit += amount;
        
        return `
          <tr>
            <td>${distribution.account_name || 'N/A'}</td>
            <td>${distribution.project_name || '-'}</td>
            <td>${distribution.department_name || '-'}</td>
            <td>${distribution.supplier_name || '-'}</td>
            <td class="text-end">${distribution.payment_type === 'debit' ? '₱' + amount.toLocaleString('en-PH', {minimumFractionDigits: 2}) : '-'}</td>
            <td class="text-end">${distribution.payment_type === 'credit' ? '₱' + amount.toLocaleString('en-PH', {minimumFractionDigits: 2}) : '-'}</td>
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
    } else {
      console.log('No distributions found');
    }
    
    details.innerHTML = `
      <div><strong>Type:</strong> ${transactionType}</div>
      <div><strong>Voucher:</strong> ${trn.reference_no}</div>
      <div><strong>Date:</strong> ${new Date(trn.transaction_date).toLocaleDateString()}</div>
      <div><strong>Payee:</strong> ${trn.payee_name || ''}</div>
      <div><strong>Amount:</strong> ₱${amount}</div>
      <hr class="my-2">
      <div class="text-muted">Distribution follows header decision on approval/rejection.</div>
      ${distributionTable}
    `;
  } else {
    console.log('No transaction data received');
    details.textContent = 'No details available.';
  }
  
  // Clear comment field when opening modal
  document.getElementById('notifComment').value = '';
  
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
    
    console.log('Approving transaction with ID:', currentNotifTransactionId);
    console.log('Transaction type:', currentTransactionType);
    
    // Show confirmation dialog
    if (!confirm('Do you want to approve this transaction?')) {
      return;
    }
    
         fetch(APP_URL + '/api/transactions/' + currentTransactionType + '/' + currentNotifTransactionId + '/approve', { method: 'POST' })
      .then(r => r.json()).then(resp => {
        if (resp.success) {
          bootstrap.Modal.getInstance(document.getElementById('notifModal')).hide();
                     // Show success message
           if (window.EARS && window.EARS.showAlert) {
             window.EARS.showAlert('Transaction approved successfully!', 'success');
           } else {
             showAutoDismissAlert('Transaction approved successfully!', 'success');
           }
          // Mark the specific notification as read
          if (currentNotificationId) {
            console.log('Marking notification as read:', currentNotificationId);
            fetch(APP_URL + '/api/notifications/' + currentNotificationId + '/mark-read', { method: 'POST' })
              .then(r => r.json())
              .then(resp => {
                console.log('Mark as read response:', resp);
                // Reload notification count and list after marking as read
                setTimeout(() => {
                  reloadNotificationCount();
                  reloadNotificationList();
                }, 500);
              })
              .catch(error => {
                console.error('Error marking notification as read:', error);
              });
          } else {
            console.log('No currentNotificationId available, using fallback');
            // Fallback: reload notification count and list after a short delay
            setTimeout(() => {
              reloadNotificationCount();
              reloadNotificationList();
            }, 500);
          }
                 } else {
           // Show error message
           if (window.EARS && window.EARS.showAlert) {
             window.EARS.showAlert('Failed to approve transaction: ' + (resp.message || 'Unknown error'), 'danger');
           } else {
             showAutoDismissAlert('Failed to approve transaction: ' + (resp.message || 'Unknown error'), 'danger');
           }
         }
       }).catch(error => {
         // Show error message
         if (window.EARS && window.EARS.showAlert) {
           window.EARS.showAlert('Failed to approve transaction: ' + error.message, 'danger');
         } else {
           showAutoDismissAlert('Failed to approve transaction: ' + error.message, 'danger');
         }
       });
  }
  if (e.target && e.target.id === 'notifRejectBtn') {
    console.log('Reject button clicked');
    console.log('Current transaction ID:', currentNotifTransactionId);
    console.log('Transaction type:', currentTransactionType);
    
    if (!currentNotifTransactionId) {
      console.error('No transaction ID found for rejection');
      return;
    }
    
    const reason = document.getElementById('notifComment').value.trim();
    console.log('Rejection reason:', reason);
    
    // Validate that a reason is provided for rejection
    if (!reason) {
      if (window.EARS && window.EARS.showAlert) {
        window.EARS.showAlert('Please provide a reason for rejection.', 'warning');
      } else {
        showAutoDismissAlert('Please provide a reason for rejection.', 'warning');
      }
      return;
    }
    
    // Show confirmation dialog
    if (!confirm('Do you want to reject this transaction?')) {
      return;
    }
    
    console.log('Sending rejection request to:', APP_URL + '/api/transactions/' + currentTransactionType + '/' + currentNotifTransactionId + '/reject');
    
    fetch(APP_URL + '/api/transactions/' + currentTransactionType + '/' + currentNotifTransactionId + '/reject', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ return_reason: reason })
    }).then(r => {
      console.log('Rejection response status:', r.status);
      return r.json();
    }).then(resp => {
      console.log('Rejection response:', resp);
      if (resp.success) {
        bootstrap.Modal.getInstance(document.getElementById('notifModal')).hide();
        // Show success message with guaranteed auto-dismiss
        if (window.EARS && window.EARS.showAlert) {
          window.EARS.showAlert('Transaction rejected successfully!', 'success');
        } else {
          showAutoDismissAlert('Transaction rejected successfully!', 'success');
        }
        // Mark the specific notification as read
        if (currentNotificationId) {
          console.log('Marking notification as read (rejection):', currentNotificationId);
          fetch(APP_URL + '/api/notifications/' + currentNotificationId + '/mark-read', { method: 'POST' })
            .then(r => r.json())
            .then(resp => {
              console.log('Mark as read response (rejection):', resp);
              // Reload notification count and list after marking as read
              setTimeout(() => {
                reloadNotificationCount();
                reloadNotificationList();
              }, 500);
            })
            .catch(error => {
              console.error('Error marking notification as read (rejection):', error);
            });
        } else {
          console.log('No currentNotificationId available for rejection, using fallback');
          // Fallback: reload notification count and list after a short delay
          setTimeout(() => {
            reloadNotificationCount();
            reloadNotificationList();
          }, 500);
        }
      } else {
        // Show error message with guaranteed auto-dismiss
        if (window.EARS && window.EARS.showAlert) {
          window.EARS.showAlert('Failed to reject transaction: ' + (resp.message || 'Unknown error'), 'danger');
        } else {
          showAutoDismissAlert('Failed to reject transaction: ' + (resp.message || 'Unknown error'), 'danger');
        }
      }
    }).catch(error => {
      console.error('Rejection error:', error);
      // Show error message with guaranteed auto-dismiss
      if (window.EARS && window.EARS.showAlert) {
        window.EARS.showAlert('Failed to reject transaction: ' + error.message, 'danger');
      } else {
        showAutoDismissAlert('Failed to reject transaction: ' + error.message, 'danger');
      }
    });
  }
});

// Clean up modal when it's hidden
document.getElementById('notifModal').addEventListener('hidden.bs.modal', function() {
  // Reset comment field
  document.getElementById('notifComment').value = '';
  // Reset current transaction ID
  currentNotifTransactionId = null;
  // Reset current transaction reference number
  currentTransactionReference = null;
  // Clear any existing alerts to prevent conflicts
  const alertContainer = document.getElementById('globalAlertContainer');
  if (alertContainer) {
    alertContainer.innerHTML = '';
  }
});
</script>