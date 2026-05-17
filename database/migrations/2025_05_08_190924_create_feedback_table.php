<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ChatFeedbackEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_feedback', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('chat_message_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('feedback', ChatFeedbackEnum::getValues());
            $table->timestamps();

            // Unique constraint to ensure a user can only provide feedback once per message
            $table->unique(['chat_message_id', 'user_id']);

            // Foreign key constraints to ensure referential integrity
            $table->foreign('chat_message_id')
                  ->references('id')
                  ->on('chat_messages')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Composite index for faster lookup by message and user
            $table->index(['chat_message_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_feedback');
    }
};