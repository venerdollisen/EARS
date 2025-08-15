# Role-Based Access Control (RBAC) System

## Overview
The EARS application implements a comprehensive role-based access control system to manage user permissions and menu visibility based on user roles. The system includes both client-side (UI) and server-side (controller) authorization checks to ensure complete security.

## User Roles

### 1. Admin (`admin`)
- **Full system access**
- Can access all features and menus
- Can manage all users and system settings
- Can perform all financial operations
- Can approve/reject transactions
- Can update payment statuses

### 2. Manager (`manager`)
- **Management level access**
- Can access most features except user management
- Can view reports and audit trails
- Can manage file maintenance and parameters
- Can approve transactions
- Can update payment statuses

### 3. Assistant (`user`)
- **Limited access for data entry**
- Can only access transaction entry features
- Cannot access system configuration
- Cannot view audit trails or manage users
- Can only view their own profile settings
- **Cannot update payment statuses** (locked to "Pending" by default)

## Menu Access by Role

| Menu Item | Admin | Manager | Assistant |
|-----------|-------|---------|-----------|
| Dashboard | ✅ | ✅ | ✅ |
| Parameters | ✅ | ✅ | ❌ |
| File Maintenance | ✅ | ✅ | ❌ |
| Transaction Management | ✅ | ✅ | ✅ |
| User Management | ✅ | ❌ | ❌ |
| Summary | ✅ | ✅ | ✅ |
| Reports | ✅ | ✅ | ✅ |
| Audit Trail | ✅ | ✅ | ❌ |
| Settings > Profile | ✅ | ✅ | ✅ |
| Settings > General | ✅ | ✅ | ❌ |

## Transaction Approval Workflow

### Cash Receipt Approval Process
1. **Assistant creates cash receipt** → Status automatically set to "Pending"
2. **Admin/Manager reviews** → Can update status to "Approved", "Rejected", or "On Hold"
3. **Notifications sent** → Creator notified when status changes
4. **Audit trail logged** → All status changes are tracked

### Status Fields for Cash Receipts
- **Payment Status**: Pending, Approved, Rejected, On Hold
- **CR Status**: Pending, Approved, Rejected  
- **CR Checked**: Pending, Checked, Unchecked
- **Return Reason**: Text field for rejection reasons

### Status Permissions
- **Assistants**: Can only create with "Pending" status (fields are disabled)
- **Admins/Managers**: Can update all status fields
- **Status changes trigger notifications** to transaction creators

## Security Implementation

### 1. Client-Side Security (UI)
- Menu items are hidden based on user permissions
- Clean interface for different user roles
- Prevents accidental access through UI
- Status fields are disabled for assistants

### 2. Server-Side Security (Controllers) ⚠️ **CRITICAL**
- **All routes are protected with server-side authorization checks**
- Direct URL access is blocked for unauthorized users
- API endpoints are secured with permission checks
- 403 Forbidden responses for unauthorized access attempts
- **Status enforcement**: Assistants cannot bypass "Pending" status

### 3. Authorization Methods

#### Helper Functions (Client-Side)
The system uses helper functions defined in `views/layouts/main.php`:

```php
// Check if user is an assistant
isAssistant($user)

// Check if user is an admin
isAdmin($user)

// Check if user is a manager
isManager($user)

// Check specific permissions
hasPermission($user, $permission)
```

#### Server-Side Authorization Methods
Controllers use the `AuthorizationTrait` for server-side checks:

```php
// Require specific permission
$this->requirePermission('file_maintenance');

// Require admin privileges
$this->requireAdmin();

// Require admin or manager
$this->requireAdminOrManager();

// Require non-assistant role
$this->requireNotAssistant();
```

### Permission Types
- `file_maintenance` - Access to chart of accounts, projects, departments, etc.
- `parameters` - Access to accounting parameters
- `user_management` - Access to user management features
- `audit_trail` - Access to system audit logs
- `system_settings` - Access to general system settings
- `transaction_entries` - Access to transaction entry forms
- `reports` - Access to financial reports
- `summary` - Access to summary views
- `profile_settings` - Access to personal profile settings

### Usage Examples

#### In Views (Client-Side)
```php
<?php if (hasPermission($user, 'file_maintenance')): ?>
    <!-- Show file maintenance menu -->
<?php endif; ?>

<?php if (isAssistant($user)): ?>
    <!-- Show assistant-specific content -->
<?php endif; ?>
```

#### In Controllers (Server-Side)
```php
public function chartOfAccounts() {
    $this->requireAuth();
    $this->requirePermission('file_maintenance');
    
    // Controller logic here
}
```

## Protected Controllers

The following controllers implement server-side authorization:

1. **FileMaintenanceController** - All methods require `file_maintenance` permission
2. **ParametersController** - All methods require `parameters` permission
3. **UserManagementController** - All methods require `user_management` permission
4. **AuditTrailController** - All methods require `audit_trail` permission
5. **SettingsController** - System settings require `system_settings` permission

## Error Handling

### 403 Forbidden Page
When users attempt to access unauthorized resources:
- Custom 403 error page with clear messaging
- Navigation options to return to dashboard
- Professional error presentation

### API Responses
For API requests, unauthorized access returns:
```json
{
    "error": "Access denied. Insufficient permissions."
}
```

## Security Considerations

1. **Server-side validation**: All permission checks are implemented in controllers
2. **Database level**: Ensure proper database constraints and access controls
3. **Session management**: Validate user sessions and roles on each request
4. **Audit logging**: Log all access attempts and permission changes
5. **Direct URL protection**: Users cannot bypass security by typing URLs directly
6. **API endpoint security**: All AJAX calls are protected with permission checks
7. **Status enforcement**: Assistants cannot modify transaction statuses

## Testing Security

To test the security implementation:

1. **Login as an assistant user**
2. **Try to access restricted URLs directly**:
   - `/file-maintenance/chart-of-accounts`
   - `/parameters/accounting`
   - `/users`
   - `/audit-trail`
   - `/settings/general`
3. **Verify you get a 403 Forbidden response**
4. **Test transaction approval workflow**:
   - Create a cash receipt as assistant (should be "Pending")
   - Login as admin/manager and update status
   - Verify notifications are sent

## Adding New Permissions

To add a new permission:

1. Add the permission to the `hasPermission()` function in `AuthorizationTrait.php`
2. Add the permission to the `hasPermission()` function in `views/layouts/main.php`
3. Update the permission matrix in this documentation
4. Implement the permission check in relevant views and controllers
5. Test with different user roles

## Best Practices

1. **Principle of Least Privilege**: Users should only have access to what they need
2. **Consistent Implementation**: Use the helper functions consistently across the application
3. **Server-Side First**: Always implement server-side checks before client-side
4. **Regular Review**: Periodically review and update permission assignments
5. **Documentation**: Keep this documentation updated as permissions change
6. **Testing**: Regularly test security with different user roles
7. **Status Workflow**: Always enforce proper approval workflows for transactions
