<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('subscription_status', 20)->default('trialing')->after('is_active');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            $table->timestamp('access_until')->nullable()->after('trial_ends_at');
            $table->timestamp('past_due_since')->nullable()->after('access_until');

            $table->string('billing_name')->nullable();
            $table->string('billing_document', 20)->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_phone', 20)->nullable();
            $table->string('asaas_customer_id')->nullable()->index();
            $table->string('asaas_subscription_id')->nullable()->index();
        });

        // Lojas que já existem ganham o período de teste a partir de hoje.
        DB::table('stores')->update([
            'plan_id' => DB::table('plans')->orderBy('id')->value('id'),
            'subscription_status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn([
                'subscription_status', 'trial_ends_at', 'access_until', 'past_due_since',
                'billing_name', 'billing_document', 'billing_email', 'billing_phone',
                'asaas_customer_id', 'asaas_subscription_id',
            ]);
        });
    }
};
