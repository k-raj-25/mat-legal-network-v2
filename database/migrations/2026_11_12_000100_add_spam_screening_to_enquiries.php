<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** AI spam screening: the verdict and the admin's review are kept next to the enquiry, nothing is ever deleted. */
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->string('spam_check', 12)->nullable()->after('status');      // pending | passed | flagged | failed (null = AI screening was off)
            $table->unsignedTinyInteger('spam_confidence')->nullable()->after('spam_check'); // 0-100: how sure the AI is that it IS spam
            $table->string('spam_reason', 300)->nullable()->after('spam_confidence');
            $table->string('spam_model', 60)->nullable()->after('spam_reason');
            $table->timestamp('spam_checked_at')->nullable()->after('spam_model');
            $table->string('spam_review', 12)->nullable()->after('spam_checked_at'); // confirmed | not_spam
            $table->foreignId('spam_reviewed_by')->nullable()->after('spam_review')->constrained('users')->nullOnDelete();
            $table->timestamp('spam_reviewed_at')->nullable()->after('spam_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('spam_reviewed_by');
            $table->dropColumn(['spam_check', 'spam_confidence', 'spam_reason', 'spam_model', 'spam_checked_at', 'spam_review', 'spam_reviewed_at']);
        });
    }
};
