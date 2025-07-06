<?php

namespace App\Http\Controllers;

use App\Models\FileUpload;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => 'required|file|max:1024',
        ]);

        // アップロード済みファイル数をチェック
        $existingFileCount = FileUpload::where('upload_status', 'completed')->count();
        $newFileCount = count($request->file('files'));
        $totalCount = $existingFileCount + $newFileCount;

        if ($totalCount > 10) {
            return response()->json([
                'error' => "合計ファイル数が10を超えます。現在{$existingFileCount}ファイルがアップロード済みです。追加可能なファイル数は" . (10 - $existingFileCount) . "ファイルまでです。",
            ], 400);
        }

        $uploadedFiles = [];

        foreach ($request->file('files') as $file) {
            $originalName = $file->getClientOriginalName();
            $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $mimeType = $file->getClientMimeType();
            $fileSize = $file->getSize();

            $fileUpload = FileUpload::create([
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'file_path' => 'uploads/' . $storedName,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'upload_status' => 'pending',
            ]);

            $uploadedFiles[] = $fileUpload;
        }

        return response()->json([
            'message' => 'Files prepared for upload',
            'files' => $uploadedFiles,
        ]);
    }

    public function processUpload(Request $request, FileUpload $fileUpload): JsonResponse
    {
        if (!$fileUpload->isPending() && !$fileUpload->canRetry()) {
            return response()->json([
                'error' => 'File cannot be uploaded',
            ], 400);
        }

        try {
            $file = $request->file('file');

            if (!$file) {
                $fileUpload->markAsFailed('No file provided');
                return response()->json(['error' => 'No file provided'], 400);
            }

            $fileUpload->markAsUploading();

            $path = $file->storeAs('uploads', $fileUpload->stored_name, 'public');

            $fileUpload->markAsCompleted();

            return response()->json([
                'message' => 'File uploaded successfully',
                'file' => $fileUpload->fresh(),
            ]);

        } catch (\Exception $e) {
            $fileUpload->markAsFailed($e->getMessage());

            return response()->json([
                'error' => 'Upload failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function retry(FileUpload $fileUpload): JsonResponse
    {
        \Log::info('Retry attempt', [
            'file_id' => $fileUpload->id,
            'current_status' => $fileUpload->upload_status,
            'retry_count' => $fileUpload->retry_count,
            'can_retry' => $fileUpload->canRetry()
        ]);

        if (!$fileUpload->canRetry()) {
            return response()->json([
                'error' => 'File cannot be retried',
                'details' => [
                    'status' => $fileUpload->upload_status,
                    'retry_count' => $fileUpload->retry_count,
                    'max_retries' => 3
                ]
            ], 400);
        }

        try {
            $fileUpload->update([
                'upload_status' => 'pending',
                'error_message' => null,
            ]);

            \Log::info('File reset for retry', ['file_id' => $fileUpload->id]);

            return response()->json([
                'message' => 'File ready for retry',
                'file' => $fileUpload->fresh(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Retry update failed', [
                'file_id' => $fileUpload->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to update file status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function status(FileUpload $fileUpload): JsonResponse
    {
        return response()->json([
            'file' => $fileUpload,
        ]);
    }

    public function index(): JsonResponse
    {
        $files = FileUpload::orderBy('created_at', 'desc')->get();

        return response()->json([
            'files' => $files,
        ]);
    }

    public function download(FileUpload $fileUpload)
    {
        if (!$fileUpload->isCompleted()) {
            abort(404, 'File not found or not completed');
        }

        $filePath = storage_path('app/public/uploads/' . $fileUpload->stored_name);

        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath, $fileUpload->original_name);
    }

    public function delete(FileUpload $fileUpload): JsonResponse
    {
        if ($fileUpload->isCompleted()) {
            Storage::disk('public')->delete('uploads/' . $fileUpload->stored_name);
        }

        $fileUpload->delete();

        return response()->json([
            'message' => 'File deleted successfully',
        ]);
    }
}
