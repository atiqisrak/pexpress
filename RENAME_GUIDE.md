# Polar Express - Rename Guide

## HR → Agency, Delivery → HR

This guide documents the renaming changes needed to align code with the updated terminology where:

- **HR** (Human Resources) is now **Agency**
- **Delivery** is now **HR** (Human Resources)

## Terminology Changes

### Role Names

- `polar_hr` → `polar_agency` (or keep as is, but update labels)
- `polar_delivery` → `polar_hr` (or keep as is, but update labels)

### Display Labels

- "Polar HR" → "Polar Agency" or "Agency"
- "Polar Delivery" → "Polar HR" or "HR"

## Files That Need Renaming

### Core Files

1. **Roles File**: `pexpress/roles.php`

   - Function: `polar_create_roles()` - Update role names and capabilities
   - Function: `polar_remove_roles()` - Update role removal

2. **Admin Files**:

   - `pexpress/admin/class-pexpress-admin-menus.php` - Update menu labels
   - `pexpress/admin/class-pexpress-admin-settings.php` - Update role descriptions
   - `pexpress/admin/class-pexpress-admin-dashboards.php` - Update dashboard rendering
   - `pexpress/admin/class-pexpress-admin-roles.php` - Update role management

3. **Templates**:

   - `pexpress/templates/hr-dashboard.php` - Already updated to "Agency Dashboard"
   - `pexpress/templates/delivery-dashboard.php` - Should be renamed to `hr-dashboard.php` or updated labels

4. **Shortcodes**:
   - `pexpress/shortcodes.php` - Update shortcode names and labels

## Variable Name Changes

### In PHP Code

- `$hr_users` → `$agency_users`
- `$delivery_users` → `$hr_users`
- `polar_hr` role → `polar_agency` role (or keep role key, update labels)
- `polar_delivery` role → `polar_hr` role (or keep role key, update labels)

### In JavaScript

- `polar_hr` → `polar_agency`
- `polar_delivery` → `polar_hr`

### In CSS Classes

- `.polar-hr-dashboard` → `.polar-agency-dashboard`
- `.polar-delivery-dashboard` → `.polar-hr-dashboard`

## Function Name Changes

### Suggested Renames

1. `render_hr_dashboard()` → `render_agency_dashboard()`
2. `render_delivery_dashboard()` → `render_hr_dashboard()`
3. `get_hr_users()` → `get_agency_users()`
4. `get_delivery_users()` → `get_hr_users()`

## Database/Meta Key Changes

### Order Meta Keys

- `_polar_hr_user_id` → `_polar_agency_user_id` (if changed)
- `_polar_delivery_user_id` → `_polar_hr_user_id` (if changed)

**Note**: Be careful with meta key changes as they affect existing data. Consider keeping meta keys as-is and only updating display labels.

## Migration Strategy

### Option 1: Keep Role Keys, Update Labels (Recommended)

- Keep `polar_hr` and `polar_delivery` role keys in database
- Only update display labels and function names
- Less risky, no data migration needed

### Option 2: Full Rename

- Rename role keys in database
- Update all references
- Requires data migration script
- More comprehensive but riskier

## Current Status

### Already Updated (Display Labels)

- ✅ Setup wizard: "HR Dashboard" → "Agency Dashboard"
- ✅ HR Dashboard template: Title shows "Agency Dashboard"
- ✅ Settings page: Role descriptions updated
- ✅ History cards: "HR" label for delivery role

### Needs Update

- ⚠️ Role keys in database (if changing)
- ⚠️ Function names (optional, for clarity)
- ⚠️ Variable names (optional, for clarity)
- ⚠️ File names (optional, for clarity)

## Implementation Notes

1. **Backward Compatibility**: Consider keeping role keys (`polar_hr`, `polar_delivery`) as-is to maintain compatibility with existing data
2. **Display Only**: Update only display labels and user-facing text
3. **Gradual Migration**: Update code gradually, testing at each step
4. **Documentation**: Update all inline comments and documentation

## Testing Checklist

After renaming:

- [ ] User roles still work correctly
- [ ] Order assignments function properly
- [ ] Dashboards load correctly
- [ ] Status updates work
- [ ] SMS notifications work
- [ ] History tracking works
- [ ] All admin pages accessible

## Recommended Approach

**Keep role keys unchanged** (`polar_hr`, `polar_delivery`) but update:

1. All display labels in templates
2. All user-facing text
3. Function names for clarity (optional)
4. Variable names for clarity (optional)
5. File names for clarity (optional)

This minimizes risk while achieving the desired user experience.
