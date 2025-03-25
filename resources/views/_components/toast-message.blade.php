<div x-data="toastHandler()" x-init="init()" x-cloak>
    <template x-teleport="body">
        <div class="fixed bottom-4 right-4 sm:bottom-8 sm:right-8 space-y-3 z-50">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-show="toast.show" x-transition
                    class="flex items-center w-full max-w-xs sm:w-xs space-x-3 p-4
                           bg-white border border-zinc-200 rounded-lg shadow-lg">

                    <template x-if="toast.type === 'success'">
                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-4l5-5-1.414-1.414L9 11.172l-1.586-1.586L6 11l3 3z"/>
                        </svg>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 7h2v4H9V7zm0 6h2v2H9v-2z" clip-rule="evenodd"/>
                        </svg>
                    </template>
                    <template x-if="toast.type === 'info'">
                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M18 10c0 4.418-3.582 8-8 8s-8-3.582-8-8 3.582-8 8-8 8 3.582 8 8zM11 10h-2v5h2v-5zm0-4h-2v2h2V6z"/>
                        </svg>
                    </template>

                    <span class="text-sm text-gray-800 font-medium" x-text="toast.message"></span>
                </div>
            </template>
        </div>
    </template>
</div>

<script>
    function toastHandler() {
        return {
            toasts: [],

            init() {
                const addToast = (message, type) => {
                    const id = Date.now() + Math.random();
                    const toast = { id, message, type, show: true };

                    this.toasts.push(toast);

                    setTimeout(() => {
                        toast.show = false;
                        setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 300);
                    }, 4000);
                };

                window.toast = (msg) => addToast(msg ?? 'Empty Info', 'info');
                window.toastSuccess = (msg) => addToast(msg ?? 'Success.', 'success');
                window.toastError = (msg) => addToast(msg ?? 'Something went wrong.', 'error');
            }
        }
    }
</script>
