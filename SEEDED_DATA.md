# Seeded Database Information

## Users Created

The database has been seeded with **6 users** (1 from AdminUserSeeder + 5 from SampleDataSeeder):

### System Users

1. **System Administrator** (admin@activitytracker.com) - Admin in IT Support
2. **John Manager** (manager@example.com) - Admin in Management
3. **Sarah Supervisor** (supervisor@example.com) - Supervisor in IT Support
4. **Mike Johnson** (mike@example.com) - Member in IT Support
5. **Lisa Chen** (lisa@example.com) - Member in Customer Service
6. **David Wilson** (david@example.com) - Member in Operations

### Login Credentials

-   **Password for all users**: `password123`
-   **Employee IDs**: ADMIN001, MGR001, SUP001, EMP001, EMP002, EMP003

## Activities Created

The database contains **8 sample activities** with realistic scenarios:

### Today's Activities (5)

1. **Fix server connectivity issue** - Pending (High Priority)
    - Created by: Sarah Supervisor, Assigned to: Mike Johnson
2. **Update customer database records** - Done (Medium Priority)
    - Created by: John Manager, Assigned to: Lisa Chen
3. **Prepare monthly operations report** - Pending (Medium Priority)
    - Created by: John Manager, Assigned to: David Wilson
4. **Install security patches on workstations** - Pending (High Priority)
    - Created by: Sarah Supervisor, Assigned to: Mike Johnson
5. **Customer service training session** - Done (Low Priority)
    - Created by: Sarah Supervisor, Assigned to: Lisa Chen

### Yesterday's Activities (3)

1. **Backup system maintenance** - Done (Medium Priority)
    - Created by: John Manager, Assigned to: Mike Johnson
2. **Process customer refund requests** - Done (High Priority)
    - Created by: Sarah Supervisor, Assigned to: Lisa Chen
3. **Inventory audit - Office supplies** - Pending (Low Priority)
    - Created by: John Manager, Assigned to: David Wilson

## Activity Updates

The database contains **15 activity updates** including:

-   Initial creation updates for all activities
-   Completion updates for finished activities
-   Progress updates for some pending activities

## Testing the Application

You can now:

1. **Login** with any of the user credentials above
2. **View Dashboard** to see today's activities and statistics
3. **Generate Reports** to see activity trends and department performance
4. **Create New Activities** and assign them to team members
5. **Update Activity Status** and add remarks

## Database Commands

To check the seeded data:

```bash
# Count records
php artisan tinker --execute="echo 'Users: ' . App\Models\User::count() . PHP_EOL; echo 'Activities: ' . App\Models\Activity::count() . PHP_EOL; echo 'Updates: ' . App\Models\ActivityUpdate::count() . PHP_EOL;"

# View users
php artisan tinker --execute="use App\Models\User; User::all(['name', 'email', 'role', 'department'])->each(function(\$u) { echo \$u->name . ' - ' . \$u->role . ' in ' . \$u->department . PHP_EOL; });"

# View activities
php artisan tinker --execute="use App\Models\Activity; Activity::with(['creator:id,name', 'assignee:id,name'])->get(['name', 'status', 'priority', 'created_by', 'assigned_to'])->each(function(\$a) { echo \$a->name . ' - ' . \$a->status . ' (' . \$a->priority . ')' . PHP_EOL; });"
```

## Re-seeding

To refresh the database with fresh sample data:

```bash
php artisan migrate:fresh --seed
```
