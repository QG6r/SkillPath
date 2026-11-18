<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    course: { type: Object, required: true },
    module: { type: Object, required: true },
    state: { type: String, required: true },
    topics: { type: Array, required: true },
    task: { type: Object, default: null },
    can_submit: { type: Boolean, required: true },
    submissions: { type: Array, required: true },
    next_module_id: { type: Number, default: null },
});

const form = useForm({
    github_url: '',
    message: '',
});

const submit = () => {
    form.post(route('student.submissions.store', props.module.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head :title="module.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <Link :href="route('student.courses.show', course.id)" class="text-sm text-indigo-600 hover:text-indigo-800">
                        &larr; {{ course.title }}
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ module.title }}</h2>
                </div>
                <StatusBadge :value="state" />
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <p v-if="module.description" class="text-gray-600">{{ module.description }}</p>

                <!-- Lessons -->
                <div v-for="(topic, index) in topics" :key="topic.id" class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">{{ index + 1 }}. {{ topic.title }}</h3>

                    <div v-if="topic.embed_url" class="mt-4 aspect-video overflow-hidden rounded-md">
                        <iframe
                            :src="topic.embed_url"
                            :title="topic.title"
                            class="h-full w-full"
                            allowfullscreen
                            loading="lazy"
                        ></iframe>
                    </div>
                    <p v-else-if="topic.video_url" class="mt-4 text-sm">
                        <a :href="topic.video_url" target="_blank" rel="noopener noreferrer" class="text-indigo-600 underline">
                            Watch the video
                        </a>
                    </p>

                    <p v-if="topic.content" class="mt-4 whitespace-pre-line text-gray-700">{{ topic.content }}</p>
                </div>

                <p v-if="topics.length === 0" class="rounded-lg bg-white p-6 text-sm text-gray-500 shadow-sm">
                    This module has no lessons yet.
                </p>

                <!-- Final work -->
                <div v-if="task" class="rounded-lg border-2 border-indigo-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Final work: {{ task.title }}</h3>
                    <p class="mt-2 whitespace-pre-line text-gray-700">{{ task.instructions }}</p>

                    <form v-if="can_submit" class="mt-6 space-y-4" @submit.prevent="submit">
                        <div>
                            <InputLabel for="github_url" value="Link to your GitHub repository" />
                            <input
                                id="github_url"
                                v-model="form.github_url"
                                type="url"
                                placeholder="https://github.com/your-name/your-project"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError class="mt-2" :message="form.errors.github_url" />
                        </div>

                        <div>
                            <InputLabel for="message" value="Message to the teacher (optional)" />
                            <textarea
                                id="message"
                                v-model="form.message"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.message" />
                        </div>

                        <PrimaryButton :disabled="form.processing">Send for review</PrimaryButton>
                    </form>

                    <p v-else-if="state === 'pending'" class="mt-6 rounded-md bg-yellow-50 p-4 text-sm text-yellow-800">
                        Your work is waiting for the teacher. You will see the result here.
                    </p>

                    <div v-else-if="state === 'passed'" class="mt-6 rounded-md bg-green-50 p-4 text-sm text-green-800">
                        This module is passed.
                        <Link
                            v-if="next_module_id"
                            :href="route('student.modules.show', [course.id, next_module_id])"
                            class="ml-1 font-semibold underline"
                        >
                            Go to the next module
                        </Link>
                    </div>
                </div>

                <p v-else class="rounded-lg bg-white p-6 text-sm text-gray-500 shadow-sm">
                    This module has no final work.
                    <Link
                        v-if="next_module_id"
                        :href="route('student.modules.show', [course.id, next_module_id])"
                        class="font-semibold text-indigo-600 underline"
                    >
                        Go to the next module
                    </Link>
                </p>

                <!-- History of attempts -->
                <div v-if="submissions.length" class="rounded-lg bg-white shadow-sm">
                    <h3 class="border-b border-gray-100 px-6 py-4 font-semibold text-gray-900">Your attempts</h3>
                    <ul class="divide-y divide-gray-100">
                        <li v-for="submission in submissions" :key="submission.id" class="space-y-1 px-6 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <a
                                    :href="submission.github_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="break-all text-sm text-indigo-600 underline"
                                >
                                    {{ submission.github_url }}
                                </a>
                                <StatusBadge :value="submission.status" />
                            </div>
                            <p class="text-xs text-gray-400">{{ submission.created_at }}</p>
                            <p v-if="submission.message" class="text-sm text-gray-600">Your message: {{ submission.message }}</p>
                            <p v-if="submission.feedback" class="whitespace-pre-line rounded-md bg-gray-50 p-3 text-sm text-gray-800">
                                Teacher: {{ submission.feedback }}
                            </p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
