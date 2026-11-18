<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    submission: {
        type: Object,
        required: true,
    },
    history: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    status: 'correct',
    feedback: '',
});

const save = () => form.patch(route('teacher.submissions.review', props.submission.id));
</script>

<template>
    <Head title="Review" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <Link :href="route('teacher.submissions.index')" class="text-sm text-indigo-600 hover:text-indigo-800">
                        &larr; Review queue
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        {{ submission.student.name }}: {{ submission.module }}
                    </h2>
                </div>
                <StatusBadge :value="submission.status" />
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="space-y-3 rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-500">
                        {{ submission.course }} &middot; {{ submission.student.name }} ({{ submission.student.email }})
                        &middot; sent {{ submission.created_at }}
                    </p>

                    <div>
                        <p class="text-sm font-medium text-gray-700">Repository</p>
                        <a
                            :href="submission.github_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="break-all text-indigo-600 underline"
                        >
                            {{ submission.github_url }}
                        </a>
                    </div>

                    <div v-if="submission.message">
                        <p class="text-sm font-medium text-gray-700">Message from the student</p>
                        <p class="whitespace-pre-line text-gray-800">{{ submission.message }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-700">Task: {{ submission.task.title }}</p>
                        <p class="whitespace-pre-line text-sm text-gray-600">{{ submission.task.instructions }}</p>
                    </div>
                </div>

                <!-- Decision -->
                <form v-if="submission.status === 'pending'" class="space-y-4 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="save">
                    <h3 class="font-semibold text-gray-900">Your decision</h3>

                    <div class="flex gap-6">
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.status" type="radio" value="correct" class="text-green-600 focus:ring-green-500" />
                            Correct: open the next module
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.status" type="radio" value="incorrect" class="text-red-600 focus:ring-red-500" />
                            Incorrect: student sends it again
                        </label>
                    </div>
                    <InputError :message="form.errors.status" />

                    <div>
                        <InputLabel for="feedback" value="Comment for the student (required when incorrect)" />
                        <textarea
                            id="feedback"
                            v-model="form.feedback"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <InputError class="mt-2" :message="form.errors.feedback" />
                    </div>

                    <PrimaryButton :disabled="form.processing">Save decision</PrimaryButton>
                </form>

                <div v-else class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-gray-900">Decision</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        This work is reviewed.
                        <span v-if="submission.feedback" class="block whitespace-pre-line pt-2 text-gray-800">{{ submission.feedback }}</span>
                    </p>
                </div>

                <!-- Earlier attempts -->
                <div v-if="history.length" class="rounded-lg bg-white shadow-sm">
                    <h3 class="border-b border-gray-100 px-6 py-4 font-semibold text-gray-900">Earlier attempts</h3>
                    <ul class="divide-y divide-gray-100">
                        <li v-for="attempt in history" :key="attempt.id" class="space-y-1 px-6 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <a :href="attempt.github_url" target="_blank" rel="noopener noreferrer" class="break-all text-sm text-indigo-600 underline">
                                    {{ attempt.github_url }}
                                </a>
                                <StatusBadge :value="attempt.status" />
                            </div>
                            <p class="text-xs text-gray-400">{{ attempt.created_at }}</p>
                            <p v-if="attempt.feedback" class="whitespace-pre-line text-sm text-gray-700">{{ attempt.feedback }}</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
