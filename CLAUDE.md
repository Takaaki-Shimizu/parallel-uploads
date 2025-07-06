# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel-based parallel file upload system with the following requirements:
- Maximum 10 files total (system-wide limit, not per upload)
- Each file must be under 1MB
- Parallel upload processing with retry functionality
- Real-time progress tracking
- Japanese UI with responsive design

## Core Architecture

### Backend (Laravel)
- **FileUpload Model**: Tracks upload status, retry counts, timestamps, and file metadata
- **FileUploadController**: Handles upload workflow with validation, processing, retry, and download
- **Database**: `file_uploads` table with status tracking (pending → uploading → completed/failed)
- **File Storage**: Files stored in `storage/app/public/uploads/` with UUID-based naming

### Frontend (Alpine.js + Tailwind CSS)
- **ParallelFileUploader Class**: Manages concurrent uploads (max 3 simultaneous), retry logic, and file validation
- **Alpine.js Components**: Reactive UI for file selection, progress tracking, and file management
- **Responsive Design**: Z-pattern layout on desktop (2-column), sequential on mobile
- **Visual Indicators**: Green numbered badges show upload order, status-based color coding

### Key Upload Flow
1. File selection with client-side validation
2. Server registration via `/api/files/upload` (creates database records)
3. Individual file processing via `/api/files/{id}/process` 
4. Parallel upload execution with automatic retry (max 3 attempts)
5. Real-time status updates and UI refresh

## Development Commands

### Laravel
```bash
# Start development server
php artisan serve

# Run migrations
php artisan migrate

# Create storage link for file access
php artisan storage:link

# Generate model/controller/migration
php artisan make:model ModelName
php artisan make:controller ControllerName
php artisan make:migration create_table_name

# Run tests
php artisan test
./vendor/bin/phpunit
```

### Frontend
```bash
# Start Vite development server
npm run dev

# Build for production
npm run build
```

### Code Quality
```bash
# Format PHP code
./vendor/bin/pint

# Run specific test
php artisan test --filter TestMethodName
```

## File Upload Constraints

- **Total System Limit**: 10 files maximum across all users
- **File Size**: 1MB per file maximum
- **Concurrent Uploads**: 3 files processed simultaneously
- **Auto Retry**: Up to 3 attempts for failed uploads
- **Supported Operations**: Upload, download, delete, retry

## API Endpoints

- `GET /api/files` - List all uploaded files
- `POST /api/files/upload` - Register files for upload
- `POST /api/files/{id}/process` - Process individual file upload
- `POST /api/files/{id}/retry` - Retry failed upload
- `GET /api/files/{id}/download` - Download file
- `DELETE /api/files/{id}` - Delete file

## Frontend Components

- **fileUpload.js**: Main upload logic and Alpine.js integration
- **upload.blade.php**: Main upload interface with responsive design
- **navigation.blade.php**: Navigation menu with upload link

The system uses Alpine.js for reactivity and Tailwind CSS for styling, with responsive behavior that switches from Z-pattern (desktop) to sequential layout (mobile).