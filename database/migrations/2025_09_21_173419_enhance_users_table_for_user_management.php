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
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('department');
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active', 'inactive', 'locked', 'pending'])->default('active')->after('department_id');
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('last_login_at');
            }
            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('password_changed_at');
            }
            if (!Schema::hasColumn('users', 'two_factor_secret')) {
                $table->string('two_factor_secret')->nullable()->after('two_factor_enabled');
            }
            if (!Schema::hasColumn('users', 'failed_login_attempts')) {
                $table->integer('failed_login_attempts')->default(0)->after('two_factor_secret');
            }
            if (!Schema::hasColumn('users', 'account_locked_until')) {
                $table->timestamp('account_locked_until')->nullable()->after('failed_login_attempts');
            }
        });

        // Add foreign key constraint and indexes in a separate schema call
        Schema::table('users', function (Blueprint $table) {
            // Add foreign key constraint for department_id if it doesn't exist
            $foreignKeys = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'users_department_id_foreign'"));
            if ($foreignKeys->isEmpty()) {
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            }
            
            // Add indexes if they don't exist
            $indexes = collect(DB::select("SHOW INDEX FROM users"))->pluck('Key_name');
            if (!$indexes->contains('users_status_index')) {
                $table->index(['status']);
            }
            if (!$indexes->contains('users_department_id_index')) {
                $table->index(['department_id']);
            }
            if (!$indexes->contains('users_last_login_at_index')) {
                $table->index(['last_login_at']);
            }
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
            $table->dropForeign(['department_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['department_id']);
            $table->dropIndex(['last_login_at']);
            
            $table->dropColumn([
                'phone_number',
                'department_id',
                'status',
                'last_login_at',
                'password_changed_at',
                'two_factor_enabled',
                'two_factor_secret',
                'failed_login_attempts',
                'account_locked_until'
            ]);
        });
    }
};
