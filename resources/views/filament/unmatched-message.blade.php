<div class="space-y-3 text-sm">
    <div class="grid grid-cols-[auto,1fr] gap-x-4 gap-y-1 text-gray-600 dark:text-gray-400">
        <span class="font-semibold text-gray-900 dark:text-white">From</span><span>{{ $message->from_email ?? '—' }}</span>
        <span class="font-semibold text-gray-900 dark:text-white">To</span><span>{{ $message->to_email ?? '—' }}</span>
        <span class="font-semibold text-gray-900 dark:text-white">Reason</span><span>{{ $message->reason }}</span>
    </div>
    <div class="whitespace-pre-wrap rounded-lg bg-gray-50 p-4 text-gray-700 dark:bg-white/5 dark:text-gray-300">{{ $message->body ?: 'No body captured.' }}</div>
</div>
