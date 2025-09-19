<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add indexes for frequently queried columns
            $table->index(['role', 'department'], 'idx_users_role_department');
            $table->index(['department'], 'idx_users_department');
            $table->index(['role'], 'idx_users_role');
        });

        Schema::table('activities', function (Blueprint $table) {
            // Add composite indexes for common query patterns
            $table->index(['status', 'priority', 'created_at'], 'idx_activities_status_priority_created');
            $table->index(['status', 'due_date'], 'idx_activities_status_due_date');
            $table->index(['priority', 'status'], 'idx_activities_priority_status');
            $table->index(['created_at', 'status'], 'idx_activities_created_status');
            $table->index(['updated_at', 'status'], 'idx_activities_updated_status');
            
            // Add indexes for date-based queries (dashboard and reports)
            $table->index(['created_at', 'created_by'], 'idx_activities_created_creator');
            $table->index(['updated_at', 'assigned_to'], 'idx_activities_updated_assignee');
        });

        Schema::table('activity_updates', function (Blueprint $table) {
            // Add composite indexes for audit trail queries
            $table->index(['activity_id', 'created_at', 'user_id'], 'idx_updates_activity_created_user');
            $table->index(['user_id', 'new_status', 'created_at'], 'idx_updates_user_status_created');
            $table->index(['created_at', 'new_status'], 'idx_updates_created_status');
            
            // Add index for recent updates queries
            $table->index(['created_at', 'activity_id'], 'idx_updates_created_activity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role_department');
            $table->dropIndex('idx_users_department');
            $table->dropIndex('idx_users_role');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('idx_activities_status_priority_created');
            $table->dropIndex('idx_activities_status_due_date');
            $table->dropIndex('idx_activities_priority_status');
            $table->dropIndex('idx_activities_created_status');
            $table->dropIndex('idx_activities_updated_status');
            $table->dropIndex('idx_activities_created_creator');
            $table->dropIndex('idx_activities_updated_assignee');
        });

        Schema::table('activity_updates', function (Blueprint $table) {
            $table->dropIndex('idx_updates_activity_created_user');
            $table->dropIndex('idx_updates_user_status_created');
            $table->dropIndex('idx_updates_created_status');
            $table->dropIndex('idx_updates_created_activity');
        });
    }
};