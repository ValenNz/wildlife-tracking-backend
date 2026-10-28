<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('permission_id');
            $table->primary(['role_id', 'permission_id']);
            $table->foreign('permission_id', 'fk_rp_permission')->references('permission_id')->on('permissions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('role_id', 'fk_rp_role')->references('role_id')->on('roles')->cascadeOnUpdate()->cascadeOnDelete();
            $table->index('permission_id', 'idx_rp_permission');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
