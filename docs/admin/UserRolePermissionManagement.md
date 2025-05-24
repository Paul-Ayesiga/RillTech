# User Role & Permission Management

Enhanced admin dashboard user management with interactive role and permission assignment, featuring special super-admin handling and real-time visual feedback.

## Features

- 🔐 **Interactive Role Management**: Checkbox-based role assignment with visual feedback
- 👑 **Super Admin Handling**: Special treatment for super-admin role with automatic permission inheritance
- ✅ **Active State Indicators**: Clear visual indicators showing user's current roles and permissions
- 📊 **Real-time Counting**: Dynamic counters for roles and permissions
- 🎨 **Enhanced UI**: Modern interface with badges, alerts, and smooth transitions
- 💾 **Edit Mode**: Toggle between view and edit modes with save/cancel functionality
- 📱 **Responsive Design**: Works perfectly on all screen sizes

## User Detail Page Structure

### Header Section
- User basic information (name, email, avatar)
- Action buttons (Edit, Delete, etc.)
- Breadcrumb navigation

### Roles & Permissions Section
- **Edit Toggle**: Switch between view and edit modes
- **Super Admin Badge**: Special indicator for super-admin users
- **Tab Navigation**: Separate tabs for roles and permissions
- **Real-time Counters**: Show current role and permission counts

## Super Admin Implementation

### Backend Logic
```php
// In your User model or service
public function hasAllPermissions()
{
    return $this->hasRole('super-admin');
}

// Super admin automatically gets all permissions
public function getAllPermissions()
{
    if ($this->hasRole('super-admin')) {
        return Permission::all();
    }
    
    return $this->getPermissionsViaRoles();
}
```

### Frontend Detection
```vue
<script setup>
const hasSuperAdminRole = computed(() => 
  user.roles?.some(role => role.name === 'super-admin') || false
);

const userPermissionIds = computed(() => {
  if (hasSuperAdminRole.value) {
    // Super admin has all permissions
    return allPermissions.map(p => p.id);
  }
  
  // Regular users get permissions through roles
  return user.roles?.flatMap(role => 
    role.permissions?.map(perm => perm.id) || []
  ) || [];
});
</script>
```

## Role Management Interface

### Roles Tab Features
- **Checkbox Controls**: Interactive checkboxes for role assignment
- **Active Badges**: Visual indicators for currently assigned roles
- **Permission Preview**: Shows first 3 permissions with "+X more" indicator
- **Super Admin Icon**: Special shield icon for super-admin role
- **Role Information**: Guard name and permission count

### Role Card Structure
```vue
<div class="flex items-start space-x-3 p-3 rounded-lg border">
  <Checkbox
    :checked="hasRole(role.id)"
    @update:checked="toggleRole(role.id)"
    :disabled="!isEditing"
  />
  <div class="flex-1 space-y-1">
    <label class="flex items-center gap-2">
      <ShieldCheck v-if="role.name === 'super-admin'" />
      {{ role.name }}
      <Badge v-if="hasRole(role.id)">Active</Badge>
    </label>
    <p class="text-xs text-muted-foreground">
      {{ role.guard_name }} • {{ role.permissions.length }} permissions
    </p>
    <!-- Permission preview badges -->
  </div>
</div>
```

## Permission Management Interface

### Permissions Tab Features
- **Super Admin View**: Special message and icon for super-admin users
- **Permission Grid**: Organized grid layout for permission display
- **Active Indicators**: Green checkmarks for granted permissions
- **Permission Details**: Name and guard information
- **Inheritance Display**: Shows permissions from roles vs direct assignments

### Permission Display Logic
```vue
<template>
  <!-- Super Admin Special View -->
  <div v-if="hasSuperAdminRole" class="text-center py-8">
    <ShieldCheck class="h-12 w-12 text-primary" />
    <h3>Super Admin Access</h3>
    <p>Automatically has access to all permissions</p>
  </div>
  
  <!-- Regular User Permissions -->
  <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
    <div v-for="permission in userPermissions" 
         class="p-3 rounded-lg border bg-green-50">
      <Check class="h-4 w-4 text-green-600" />
      <p>{{ permission.name }}</p>
    </div>
  </div>
</template>
```

## Edit Mode Functionality

### State Management
```vue
<script setup>
const isEditing = ref(false);
const form = useForm({
  roles: user.roles?.map(role => role.id) || []
});

const saveChanges = () => {
  form.put(route('admin.users.update-roles-permissions', user.id), {
    onSuccess: () => {
      toast.success('Roles updated successfully');
      isEditing.value = false;
    }
  });
};
</script>
```

### UI Controls
```vue
<template>
  <div class="flex items-center gap-2">
    <Button v-if="!isEditing" @click="isEditing = true">
      Edit Roles
    </Button>
    <div v-else class="flex gap-2">
      <Button @click="saveChanges">Save Changes</Button>
      <Button @click="cancelEditing" variant="outline">Cancel</Button>
    </div>
  </div>
</template>
```

## Backend Implementation

### Controller Method
```php
public function updateRolesPermissions(Request $request, $id)
{
    $user = User::findOrFail($id);

    $validated = $request->validate([
        'roles' => 'required|array',
        'roles.*' => 'exists:roles,id',
    ]);

    // Sync roles
    $roles = Role::whereIn('id', $validated['roles'])->get();
    $user->syncRoles($roles);

    return redirect()->back()->with('success', 'User roles updated successfully.');
}
```

### Route Definition
```php
Route::put('users/{user}/roles-permissions', [UserController::class, 'updateRolesPermissions'])
     ->name('admin.users.update-roles-permissions');
```

## Visual Indicators

### Badge System
- **Active Role**: Green "Active" badge on assigned roles
- **Super Admin**: Special blue badge with shield icon
- **Permission Count**: Shows number in tab headers
- **Role Preview**: First 3 permissions with "+X more"

### Color Coding
- **Green**: Active/granted permissions and roles
- **Blue**: Super admin indicators
- **Gray**: Inactive/unassigned items
- **Border**: Different border colors for different states

## Responsive Design

### Mobile Optimization
- **Stacked Layout**: Roles stack vertically on mobile
- **Touch-friendly**: Large touch targets for checkboxes
- **Readable Text**: Appropriate font sizes for mobile
- **Collapsible**: Permission previews collapse on small screens

### Desktop Features
- **Grid Layout**: Multi-column permission display
- **Hover Effects**: Interactive hover states
- **Keyboard Navigation**: Full keyboard accessibility
- **Quick Actions**: Inline edit controls

## Security Considerations

### Permission Validation
- **Role Existence**: Validate role IDs exist
- **Permission Inheritance**: Ensure super-admin gets all permissions
- **Guard Matching**: Verify guard names match
- **Audit Trail**: Log role changes for security

### Access Control
```php
// Middleware or policy check
public function updateRolesPermissions(Request $request, $id)
{
    $this->authorize('manage-users');
    
    // Prevent users from modifying their own super-admin role
    if (auth()->id() == $id && $request->has('roles')) {
        $currentRoles = auth()->user()->roles->pluck('id')->toArray();
        $newRoles = $request->input('roles');
        
        if (array_diff($currentRoles, $newRoles)) {
            abort(403, 'Cannot modify your own roles');
        }
    }
}
```

## Usage Examples

### Basic Role Assignment
```vue
<!-- View user roles -->
<UserDetail :user="user" :allRoles="roles" :allPermissions="permissions" />

<!-- Edit mode -->
<Button @click="isEditing = true">Edit Roles</Button>
```

### Super Admin Detection
```vue
<template>
  <Badge v-if="user.roles.some(r => r.name === 'super-admin')">
    Super Admin
  </Badge>
</template>
```

### Permission Checking
```vue
<script setup>
const canManageUsers = computed(() => 
  user.permissions.some(p => p.name === 'manage users') ||
  user.roles.some(r => r.name === 'super-admin')
);
</script>
```

This enhanced user management system provides a comprehensive, user-friendly interface for managing roles and permissions with special handling for super-admin users and clear visual feedback for all operations.
