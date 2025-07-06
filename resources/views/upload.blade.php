<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('ファイルアップロード') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div x-data="fileUpload">
                        <!-- アップロード済みファイル一覧 -->
                        <div x-show="uploadedFiles.length > 0" class="mb-8">
                            <div class="mb-4">
                                <h3 class="text-lg font-medium text-gray-900">アップロード済みファイル</h3>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <!-- デスクトップ: Z字パターンの2列表示 -->
                                <div class="hidden lg:grid lg:grid-cols-2 gap-6">
                                    <!-- 左列: Z字パターンの奇数番目 -->
                                    <div class="space-y-3">
                                        <template x-for="(file, index) in leftFiles" :key="file.id">
                                            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200 hover:shadow-md transition-shadow relative group">
                                                <div class="flex items-start space-x-3">
                                                    <div class="flex-shrink-0 relative">
                                                        <!-- ファイルアイコン -->
                                                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                            </svg>
                                                        </div>
                                                        <!-- 順番表示 -->
                                                        <div class="absolute -top-1 -left-1 w-5 h-5 bg-green-600 text-white text-xs rounded-full flex items-center justify-center font-bold">
                                                            <span x-text="(index * 2) + 1"></span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="space-y-2">
                                                            <div>
                                                                <a 
                                                                    :href="`/api/files/${file.id}/download`"
                                                                    class="text-sm font-medium text-blue-600 hover:text-blue-800 block truncate"
                                                                    :title="file.original_name"
                                                                    x-text="file.original_name"
                                                                ></a>
                                                                <p class="text-xs text-gray-500 mt-1">
                                                                    <span x-text="(file.file_size / 1024).toFixed(1)"></span>KB
                                                                </p>
                                                            </div>
                                                            <div class="text-xs text-gray-500">
                                                                <span class="font-medium">アップロード完了</span><br>
                                                                <span x-text="new Date(file.completed_at || file.created_at).toLocaleDateString('ja-JP', {
                                                                    year: 'numeric',
                                                                    month: '2-digit',
                                                                    day: '2-digit',
                                                                    hour: '2-digit',
                                                                    minute: '2-digit'
                                                                })"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- 削除ボタン -->
                                                    <button 
                                                        @click="deleteUploadedFile(file.id)"
                                                        class="absolute top-3 right-3 w-7 h-7 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                                                        title="ファイルを削除"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    
                                    <!-- 右列: Z字パターンの偶数番目 -->
                                    <div class="space-y-3" x-show="rightFiles.length > 0">
                                        <template x-for="(file, index) in rightFiles" :key="file.id">
                                            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200 hover:shadow-md transition-shadow relative group">
                                                <div class="flex items-start space-x-3">
                                                    <div class="flex-shrink-0 relative">
                                                        <!-- ファイルアイコン -->
                                                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                            </svg>
                                                        </div>
                                                        <!-- 順番表示 -->
                                                        <div class="absolute -top-1 -left-1 w-5 h-5 bg-green-600 text-white text-xs rounded-full flex items-center justify-center font-bold">
                                                            <span x-text="(index * 2) + 2"></span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="space-y-2">
                                                            <div>
                                                                <a 
                                                                    :href="`/api/files/${file.id}/download`"
                                                                    class="text-sm font-medium text-blue-600 hover:text-blue-800 block truncate"
                                                                    :title="file.original_name"
                                                                    x-text="file.original_name"
                                                                ></a>
                                                                <p class="text-xs text-gray-500 mt-1">
                                                                    <span x-text="(file.file_size / 1024).toFixed(1)"></span>KB
                                                                </p>
                                                            </div>
                                                            <div class="text-xs text-gray-500">
                                                                <span class="font-medium">アップロード完了</span><br>
                                                                <span x-text="new Date(file.completed_at || file.created_at).toLocaleDateString('ja-JP', {
                                                                    year: 'numeric',
                                                                    month: '2-digit',
                                                                    day: '2-digit',
                                                                    hour: '2-digit',
                                                                    minute: '2-digit'
                                                                })"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- 削除ボタン -->
                                                    <button 
                                                        @click="deleteUploadedFile(file.id)"
                                                        class="absolute top-3 right-3 w-7 h-7 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                                                        title="ファイルを削除"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- モバイル: 1列表示（1から10まで順番） -->
                                <div class="lg:hidden space-y-3">
                                    <template x-for="(file, index) in uploadedFiles" :key="file.id">
                                        <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200 hover:shadow-md transition-shadow relative group">
                                            <div class="flex items-start space-x-3">
                                                <div class="flex-shrink-0 relative">
                                                    <!-- ファイルアイコン -->
                                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                        </svg>
                                                    </div>
                                                    <!-- 順番表示 -->
                                                    <div class="absolute -top-1 -left-1 w-5 h-5 bg-green-600 text-white text-xs rounded-full flex items-center justify-center font-bold">
                                                        <span x-text="index + 1"></span>
                                                    </div>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="space-y-2">
                                                        <div>
                                                            <a 
                                                                :href="`/api/files/${file.id}/download`"
                                                                class="text-sm font-medium text-blue-600 hover:text-blue-800 block truncate"
                                                                :title="file.original_name"
                                                                x-text="file.original_name"
                                                            ></a>
                                                            <p class="text-xs text-gray-500 mt-1">
                                                                <span x-text="(file.file_size / 1024).toFixed(1)"></span>KB
                                                            </p>
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            <span class="font-medium">アップロード完了</span><br>
                                                            <span x-text="new Date(file.completed_at || file.created_at).toLocaleDateString('ja-JP', {
                                                                year: 'numeric',
                                                                month: '2-digit',
                                                                day: '2-digit',
                                                                hour: '2-digit',
                                                                minute: '2-digit'
                                                            })"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- 削除ボタン -->
                                                <button 
                                                    @click="deleteUploadedFile(file.id)"
                                                    class="absolute top-3 right-3 w-7 h-7 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                                                    title="ファイルを削除"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <!-- アップロードエリア -->
                        <div class="mb-6">
                            <div 
                                class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center transition-colors duration-200"
                                :class="{ 
                                    'border-blue-500 bg-blue-50': dragOver && uploadedFiles.length < 10, 
                                    'border-gray-300': !dragOver,
                                    'opacity-50': uploadedFiles.length >= 10
                                }"
                                @dragover.prevent="if (uploadedFiles.length < 10) dragOver = true"
                                @dragleave.prevent="dragOver = false"
                                @drop.prevent="if (uploadedFiles.length < 10) handleDrop($event)"
                            >
                                <div class="space-y-4">
                                    <div class="text-6xl text-gray-400">
                                        📁
                                    </div>
                                    <div>
                                        <p class="text-lg font-medium text-gray-900">
                                            ファイルをドラッグ&ドロップ
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            または
                                        </p>
                                    </div>
                                    <div>
                                        <input 
                                            type="file" 
                                            multiple 
                                            class="hidden" 
                                            id="fileInput"
                                            @change="handleFileInput"
                                            :disabled="uploadedFiles.length >= 10"
                                        >
                                        <label 
                                            for="fileInput"
                                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150 cursor-pointer"
                                            :class="{ 
                                                'opacity-50 cursor-not-allowed': uploading || uploadedFiles.length >= 10,
                                                'pointer-events-none': uploadedFiles.length >= 10
                                            }"
                                        >
                                            <span x-show="!uploading">ファイルを選択</span>
                                            <span x-show="uploading">アップロード中...</span>
                                        </label>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <p>最大10ファイル、各ファイル1MB未満</p>
                                        <p x-show="uploadedFiles.length > 0 && uploadedFiles.length < 10" class="text-orange-600">
                                            現在<span x-text="uploadedFiles.length"></span>ファイルアップロード済み。
                                            あと<span x-text="10 - uploadedFiles.length"></span>ファイル追加可能
                                        </p>
                                        <p x-show="uploadedFiles.length >= 10" class="text-red-600 font-medium">
                                            ファイル数上限に達しました（10ファイル）
                                        </p>
                                        <p>並行アップロード対応、失敗時自動リトライ</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- アップロードボタン -->
                        <div x-show="files.length > 0 && !uploading" class="mb-6">
                            <div class="flex justify-center">
                                <button 
                                    @click="startUpload()"
                                    class="inline-flex items-center px-6 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    アップロード開始
                                </button>
                            </div>
                        </div>

                        <!-- アップロード進捗 -->
                        <div x-show="files.length > 0" class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900">ファイル一覧</h3>
                            
                            <div class="bg-gray-50 rounded-lg p-4">
                                <template x-for="file in files" :key="file.id">
                                    <div class="flex items-center justify-between py-3 border-b border-gray-200 last:border-b-0">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center space-x-3">
                                                <div class="flex-shrink-0">
                                                    <div 
                                                        class="w-3 h-3 rounded-full"
                                                        :class="{
                                                            'bg-purple-400': file.status === 'selected',
                                                            'bg-gray-400': file.status === 'pending',
                                                            'bg-blue-500': file.status === 'uploading',
                                                            'bg-green-500': file.status === 'completed',
                                                            'bg-red-500': file.status === 'failed'
                                                        }"
                                                    ></div>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 truncate" x-text="file.original_name"></p>
                                                    <p class="text-xs text-gray-500">
                                                        <span x-text="(file.file_size / 1024).toFixed(1)"></span>KB
                                                        - 
                                                        <span x-text="getStatusText(file.status)"></span>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <!-- プログレスバー -->
                                            <div x-show="file.status === 'uploading'" class="mt-2">
                                                <div class="bg-gray-200 rounded-full h-2">
                                                    <div 
                                                        class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                        :style="`width: ${file.progress || 0}%`"
                                                    ></div>
                                                </div>
                                            </div>
                                            
                                            <!-- エラーメッセージ -->
                                            <div x-show="file.status === 'failed' && file.error" class="mt-2">
                                                <p class="text-xs text-red-600" x-text="file.error"></p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center space-x-2">
                                            <!-- リトライボタン -->
                                            <button 
                                                x-show="file.status === 'failed' && file.retryCount < 3"
                                                @click="retryFile(file.id)"
                                                class="text-xs bg-yellow-600 hover:bg-yellow-500 text-white px-3 py-1 rounded-md transition-colors"
                                            >
                                                リトライ
                                            </button>
                                            
                                            <!-- 完了アイコン -->
                                            <div x-show="file.status === 'completed'" class="text-green-600">
                                                ✅
                                            </div>
                                            
                                            <!-- 失敗アイコン -->
                                            <div x-show="file.status === 'failed'" class="text-red-600">
                                                ❌
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            
                            <!-- 全体の進捗 -->
                            <div x-show="uploading || files.some(f => f.status !== 'selected')" class="bg-white border rounded-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-700">全体の進捗</span>
                                    <span class="text-sm text-gray-500">
                                        <span x-text="files.filter(f => f.status === 'completed').length"></span> / 
                                        <span x-text="files.length"></span> 完了
                                    </span>
                                </div>
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div 
                                        class="bg-green-600 h-2 rounded-full transition-all duration-300"
                                        :style="`width: ${files.length > 0 ? (files.filter(f => f.status === 'completed').length / files.length) * 100 : 0}%`"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- 統計情報 -->
                        <div x-show="files.length > 0" class="mt-6 grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div class="bg-purple-50 rounded-lg p-4">
                                <div class="text-2xl font-bold text-purple-600" x-text="files.filter(f => f.status === 'selected').length"></div>
                                <div class="text-sm text-purple-600">選択済み</div>
                            </div>
                            <div class="bg-blue-50 rounded-lg p-4">
                                <div class="text-2xl font-bold text-blue-600" x-text="files.filter(f => f.status === 'pending').length"></div>
                                <div class="text-sm text-blue-600">待機中</div>
                            </div>
                            <div class="bg-yellow-50 rounded-lg p-4">
                                <div class="text-2xl font-bold text-yellow-600" x-text="files.filter(f => f.status === 'uploading').length"></div>
                                <div class="text-sm text-yellow-600">アップロード中</div>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4">
                                <div class="text-2xl font-bold text-green-600" x-text="files.filter(f => f.status === 'completed').length"></div>
                                <div class="text-sm text-green-600">完了</div>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4">
                                <div class="text-2xl font-bold text-red-600" x-text="files.filter(f => f.status === 'failed').length"></div>
                                <div class="text-sm text-red-600">失敗</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 隠されたファイル入力フィールド (JavaScript用) -->
    <div style="display: none;" id="hiddenFileInputs">
        <!-- JavaScriptが動的にファイル入力を追加 -->
    </div>
</x-app-layout>