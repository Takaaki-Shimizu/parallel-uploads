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
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('original_name'); // 元のファイル名
            $table->string('stored_name'); // 保存されたファイル名
            $table->string('file_path'); // ファイルパス
            $table->unsignedBigInteger('file_size'); // ファイルサイズ（バイト）
            $table->string('mime_type'); // MIMEタイプ
            $table->string('upload_status')->default('pending'); // アップロード状態: pending, uploading, completed, failed
            $table->integer('retry_count')->default(0); // リトライ回数
            $table->text('error_message')->nullable(); // エラーメッセージ
            $table->json('metadata')->nullable(); // 追加のメタデータ
            $table->timestamp('started_at')->nullable(); // アップロード開始時刻
            $table->timestamp('completed_at')->nullable(); // 完了時刻
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_uploads');
    }
};
