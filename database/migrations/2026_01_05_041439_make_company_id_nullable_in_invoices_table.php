<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['company_id']);

            // Make company_id nullable
            $table->foreignId('company_id')
                ->nullable()
                ->change();

            // Re-add foreign key
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['company_id']);

            // Make it NOT NULL again
            $table->foreignId('company_id')
                ->nullable(false)
                ->change();

            // Restore cascade delete
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();
        });
    }
};
