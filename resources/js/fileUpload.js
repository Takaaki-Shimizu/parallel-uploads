class ParallelFileUploader {
    constructor() {
        this.maxFiles = 10;
        this.maxFileSize = 1024 * 1024; // 1MB
        this.uploadQueue = [];
        this.activeUploads = new Map();
        this.maxConcurrentUploads = 3;
        this.retryLimit = 3;
        this.uploadStatus = new Map();
    }

    validateFiles(files, existingFileCount = 0) {
        const errors = [];
        
        if (files.length > this.maxFiles) {
            errors.push(`1回に選択できるファイル数は最大${this.maxFiles}ファイルまでです`);
        }

        const totalCount = existingFileCount + files.length;
        if (totalCount > this.maxFiles) {
            const allowedCount = this.maxFiles - existingFileCount;
            errors.push(`合計ファイル数が${this.maxFiles}を超えます。現在${existingFileCount}ファイルがアップロード済みです。追加可能なファイル数は${allowedCount}ファイルまでです。`);
        }

        Array.from(files).forEach((file, index) => {
            if (file.size > this.maxFileSize) {
                errors.push(`${file.name}: ファイルサイズは1MB以下である必要があります`);
            }
        });

        return errors;
    }

    async uploadFiles(files) {
        const validationErrors = this.validateFiles(files);
        if (validationErrors.length > 0) {
            throw new Error(validationErrors.join('\n'));
        }

        try {
            // ファイルを保存してから並行アップロード開始
            this.originalFiles = Array.from(files);
            await this.processParallelUploads(files);
            
            return this.originalFiles;
        } catch (error) {
            console.error('Upload failed:', error);
            throw error;
        }
    }

    async registerFiles(files) {
        const formData = new FormData();
        Array.from(files).forEach(file => {
            formData.append('files[]', file);
        });

        const response = await fetch('/api/files/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (!response.ok) {
            throw new Error('ファイル登録に失敗しました');
        }

        const data = await response.json();
        return data.files;
    }

    async processParallelUploads(files, registeredFiles = null) {
        let filesToProcess = registeredFiles;
        
        // registeredFilesが渡されていない場合のみ登録処理を実行
        if (!filesToProcess) {
            filesToProcess = await this.registerFiles(files);
        }
        
        // ファイルとregisteredFilesを対応付け
        this.fileMapping = new Map();
        filesToProcess.forEach((regFile, index) => {
            this.fileMapping.set(regFile.id, files[index]);
        });
        
        this.uploadQueue = [...filesToProcess];
        
        // 各ファイルの状態を初期化
        filesToProcess.forEach(file => {
            this.uploadStatus.set(file.id, {
                ...file,
                progress: 0,
                retryCount: 0,
                status: 'pending'
            });
        });

        // 並行アップロード開始
        const uploadPromises = [];
        for (let i = 0; i < Math.min(this.maxConcurrentUploads, this.uploadQueue.length); i++) {
            uploadPromises.push(this.processNextUpload());
        }

        await Promise.all(uploadPromises);
    }

    async processNextUpload() {
        console.log('processNextUpload started, queue length:', this.uploadQueue.length);
        
        while (this.uploadQueue.length > 0) {
            const fileInfo = this.uploadQueue.shift();
            
            if (!fileInfo) break;

            console.log('Processing file:', fileInfo.original_name);
            
            try {
                await this.uploadSingleFile(fileInfo);
            } catch (error) {
                console.error(`Upload failed for ${fileInfo.original_name}:`, error);
                await this.handleUploadFailure(fileInfo, error.message);
            }
        }
        
        console.log('processNextUpload finished');
    }

    async uploadSingleFile(fileInfo) {
        console.log('uploadSingleFile started for:', fileInfo.original_name);
        
        const file = this.fileMapping.get(fileInfo.id);
        
        if (!file) {
            console.error('File not found in mapping for ID:', fileInfo.id);
            throw new Error('ファイルが見つかりません');
        }

        console.log('File found, updating status to uploading');
        this.updateFileStatus(fileInfo.id, { status: 'uploading', progress: 0 });

        const formData = new FormData();
        formData.append('file', file);

        console.log('Sending upload request to:', `/api/files/${fileInfo.id}/process`);
        
        const response = await fetch(`/api/files/${fileInfo.id}/process`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        console.log('Upload response received:', response.status);

        if (!response.ok) {
            const errorData = await response.json();
            console.error('Upload failed with error:', errorData);
            throw new Error(errorData.message || 'アップロードに失敗しました');
        }

        const data = await response.json();
        console.log('Upload completed successfully:', data);
        
        this.updateFileStatus(fileInfo.id, { 
            status: 'completed', 
            progress: 100,
            ...data.file 
        });
    }

    async handleUploadFailure(fileInfo, errorMessage) {
        const status = this.uploadStatus.get(fileInfo.id);
        const retryCount = (status?.retryCount || 0) + 1;

        this.updateFileStatus(fileInfo.id, {
            status: 'failed',
            error: errorMessage,
            retryCount: retryCount
        });

        if (retryCount < this.retryLimit) {
            // 自動リトライ
            setTimeout(() => {
                this.retryUpload(fileInfo.id);
            }, 2000 * retryCount); // 指数バックオフ
        }
    }

    async retryUpload(fileId) {
        try {
            const response = await fetch(`/api/files/${fileId}/retry`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('リトライの準備に失敗しました');
            }

            const data = await response.json();
            this.updateFileStatus(fileId, { status: 'pending' });
            
            // キューに追加して再アップロード
            this.uploadQueue.push(data.file);
            this.processNextUpload();
        } catch (error) {
            console.error('Retry failed:', error);
        }
    }

    updateFileStatus(fileId, updates) {
        const currentStatus = this.uploadStatus.get(fileId) || {};
        const newStatus = { ...currentStatus, ...updates };
        this.uploadStatus.set(fileId, newStatus);
        
        // UI更新イベントを発火
        document.dispatchEvent(new CustomEvent('fileStatusUpdated', {
            detail: { fileId, status: newStatus }
        }));
    }

    getFileStatus(fileId) {
        return this.uploadStatus.get(fileId);
    }

    getAllFileStatuses() {
        return Array.from(this.uploadStatus.values());
    }
}

// グローバルインスタンスを作成
window.fileUploader = new ParallelFileUploader();

// Alpine.jsコンポーネント
document.addEventListener('alpine:init', () => {
    Alpine.data('fileUpload', () => ({
        files: [],
        uploadedFiles: [],
        failedFiles: [],
        selectedFiles: null,
        uploading: false,
        dragOver: false,

        async init() {
            document.addEventListener('fileStatusUpdated', (event) => {
                const { fileId, status } = event.detail;
                const fileIndex = this.files.findIndex(f => f.id === fileId);
                if (fileIndex !== -1) {
                    this.files[fileIndex] = { ...this.files[fileIndex], ...status };
                    
                    // 失敗したファイルを専用配列に追加
                    if (status.status === 'failed') {
                        const failedFile = { ...this.files[fileIndex] };
                        // 既に存在していない場合のみ追加
                        if (!this.failedFiles.find(f => f.id === fileId)) {
                            this.failedFiles.push(failedFile);
                        }
                    }
                    
                    // Alpine.jsの反応性を確保するために配列を再作成
                    this.files = [...this.files];
                }
            });
            
            // 既存のアップロード済みファイルを取得
            await this.loadUploadedFiles();
        },

        async loadUploadedFiles() {
            try {
                const response = await fetch('/api/files', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    const completedFiles = data.files.filter(file => file.upload_status === 'completed');
                    // 最新順（completed_at の降順）でソート
                    this.uploadedFiles = completedFiles.sort((a, b) => {
                        return new Date(b.completed_at || b.created_at) - new Date(a.completed_at || a.created_at);
                    });
                }
            } catch (error) {
                console.error('Failed to load uploaded files:', error);
            }
        },

        handleDrop(event) {
            event.preventDefault();
            this.dragOver = false;
            const files = event.dataTransfer.files;
            this.handleFiles(files);
        },

        handleFileInput(event) {
            const files = event.target.files;
            this.handleFiles(files);
        },

        async handleFiles(files) {
            if (this.uploading) return;

            // 新しいファイル選択時にアップロード進捗表示と失敗ファイル表示をクリア
            this.files = [];
            this.selectedFiles = null;
            this.failedFiles = [];

            // アップロード済みファイル数を考慮したバリデーション
            const existingFileCount = this.uploadedFiles.length;
            const validationErrors = window.fileUploader.validateFiles(files, existingFileCount);
            if (validationErrors.length > 0) {
                alert(validationErrors.join('\n'));
                // ファイル入力をクリア
                const fileInput = document.getElementById('fileInput');
                if (fileInput) {
                    fileInput.value = '';
                }
                return;
            }

            try {
                // ファイルを保存してUI表示用のデータを準備
                this.selectedFiles = Array.from(files);
                this.files = Array.from(files).map((file, index) => ({
                    id: `temp_${index}`,
                    original_name: file.name,
                    file_size: file.size,
                    mime_type: file.type,
                    status: 'selected',
                    progress: 0,
                    retryCount: 0
                }));
                
            } catch (error) {
                alert(error.message);
            }
        },

        async startUpload() {
            if (!this.selectedFiles || this.selectedFiles.length === 0) {
                alert('アップロードするファイルを選択してください');
                return;
            }

            if (this.uploading) return;

            this.uploading = true;
            
            try {
                // 先にファイルを登録してUI更新
                const registeredFiles = await window.fileUploader.registerFiles(this.selectedFiles);
                
                // UIファイルリストを更新
                this.files = registeredFiles.map(file => ({
                    ...file,
                    progress: 0,
                    status: 'pending'
                }));
                
                // 強制的にUI更新
                this.$nextTick(() => {
                    console.log('Files updated:', this.files);
                });
                
                // 並行アップロード開始（登録済みファイルを渡す）
                await window.fileUploader.processParallelUploads(this.selectedFiles, registeredFiles);
                
                // アップロード完了後にアップロード済みファイル一覧を更新
                await this.loadUploadedFiles();
                
                // 現在選択中のファイルをクリア
                this.files = [];
                this.selectedFiles = null;
                
            } catch (error) {
                console.error('Upload error:', error);
                alert(error.message);
            } finally {
                this.uploading = false;
            }
        },

        async retryFile(fileId) {
            await window.fileUploader.retryUpload(fileId);
        },

        async deleteUploadedFile(fileId) {
            if (!confirm('このファイルを削除しますか？')) {
                return;
            }

            try {
                const response = await fetch(`/api/files/${fileId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                });

                if (response.ok) {
                    // アップロード済みファイル一覧を再読み込み（並び順を維持）
                    await this.loadUploadedFiles();
                    alert('ファイルを削除しました');
                } else {
                    const errorData = await response.json();
                    alert('削除に失敗しました: ' + (errorData.message || '不明なエラー'));
                }
            } catch (error) {
                console.error('Delete error:', error);
                alert('削除に失敗しました');
            }
        },

        get leftFiles() {
            // Z字パターン: 1,3,5,7,9番目のファイル（奇数番目）
            return this.uploadedFiles.filter((file, index) => index % 2 === 0);
        },

        get rightFiles() {
            // Z字パターン: 2,4,6,8,10番目のファイル（偶数番目）
            return this.uploadedFiles.filter((file, index) => index % 2 === 1);
        },

        getStatusColor(status) {
            switch (status) {
                case 'selected': return 'purple';
                case 'completed': return 'green';
                case 'failed': return 'red';
                case 'uploading': return 'blue';
                default: return 'gray';
            }
        },

        getStatusText(status) {
            switch (status) {
                case 'selected': return '選択済み';
                case 'pending': return '待機中';
                case 'uploading': return 'アップロード中';
                case 'completed': return '完了';
                case 'failed': return '失敗';
                default: return '不明';
            }
        }
    }));
});