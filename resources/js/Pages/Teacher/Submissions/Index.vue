<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    submissions: {
        type: Object,
        required: true,
    },
    status: {
        type: String,
        required: true,
    },
    pending_count: {
        type: Number,
        required: true,
    },
});

const tabs = [
    { value: 'pending', label: 'Waiting' },
    { value: 'correct', label: 'Accepted' },
    { value: 'incorrect', label: 'Needs changes' },
    { value: 'all', label: 'All' },
];
</script>

<template>
    <Head title="Review queue" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Review queue</h2>
                <span class="text-sm text-gray-500">{{ pending_count }} waiting</span>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div class="flex gap-2">
                    <Link
                        v-for="tab in tabs"
                        :key="tab.value"
                        :href="route('teacher.submissions.index', { status: tab.value })"
                        class="rounded-md px-3 py-1.5 text-sm font-medium"
                        :class="status === tab.value ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                    >
                        {{ tab.label }}
                    </Link>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <p v-if="submissions.data.length === 0" class="p-6 text-center text-sm text-gray-500">
                        Nothing here.
                    </p>

                    <table v-else class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Student</th>
                                <th class="px-4 py-3">Course / module</th>
                                <th class="px-4 py-3">Sent</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="submission in submissions.data" :key="submission.id">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ submission.student }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ submission.course }} / {{ submission.module }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ submission.created_at }}</td>
                                <td class="px-4 py-3"><StatusBadge :value="submission.status" /></td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="route('teacher.submissions.show', submission.id)"
                                        class="font-semibold text-indigo-600 hover:text-indigo-800"
                                    >
                                        {{ submission.status === 'pending' ? 'Review' : 'Open' }}
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="submissions.last_page > 1" class="flex items-center justify-between text-sm">
                    <Link
                        v-if="submissions.prev_page_url"
                        :href="submissions.prev_page_url"
                        class="text-indigo-600 hover:text-indigo-800"
                    >
                        &larr; Previous
                    </Link>
                    <span v-else></span>

                    <span class="text-gray-500">Page {{ submissions.current_page }} of {{ submissions.last_page }}</span>

                    <Link
                        v-if="submissions.next_page_url"
                        :href="submissions.next_page_url"
                        class="text-indigo-600 hover:text-indigo-800"
                    >
                        Next &rarr;
                    </Link>
                    <span v-else></span>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
