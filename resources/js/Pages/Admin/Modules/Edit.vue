<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import TopicEditor from '@/Components/TopicEditor.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    course: { type: Object, required: true },
    module: { type: Object, required: true },
    topics: { type: Array, required: true },
    task: { type: Object, default: null },
});

const moduleForm = useForm({
    title: props.module.title,
    description: props.module.description ?? '',
    order: props.module.order,
});

const taskForm = useForm({
    title: props.task?.title ?? '',
    instructions: props.task?.instructions ?? '',
});

const saveModule = () => moduleForm.put(route('admin.modules.update', props.module.id), { preserveScroll: true });

const saveTask = () => taskForm.put(route('admin.tasks.update', props.module.id), { preserveScroll: true });

const removeTask = () => {
    if (confirm('Remove the final work? Submissions already sent for it are deleted too.')) {
        router.delete(route('admin.tasks.destroy', props.module.id), { preserveScroll: true });
    }
};

const removeModule = () => {
    if (confirm('Delete this module with its topics and final work?')) {
        router.delete(route('admin.modules.destroy', props.module.id));
    }
};
</script>

<template>
    <Head :title="module.title" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <Link :href="route('admin.courses.edit', course.id)" class="text-sm text-indigo-600 hover:text-indigo-800">
                    &larr; {{ course.title }}
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ module.title }}</h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Module -->
                <form class="space-y-4 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="saveModule">
                    <h3 class="font-semibold text-gray-900">Module</h3>

                    <div class="grid gap-4 sm:grid-cols-4">
                        <div class="sm:col-span-3">
                            <InputLabel for="module_title" value="Title" />
                            <TextInput id="module_title" v-model="moduleForm.title" type="text" class="mt-1 block w-full" />
                            <InputError class="mt-1" :message="moduleForm.errors.title" />
                        </div>
                        <div>
                            <InputLabel for="module_order" value="Position in the course" />
                            <input
                                id="module_order"
                                v-model.number="moduleForm.order"
                                type="number"
                                min="1"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError class="mt-1" :message="moduleForm.errors.order" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="module_description" value="Short description (optional)" />
                        <textarea
                            id="module_description"
                            v-model="moduleForm.description"
                            rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <InputError class="mt-1" :message="moduleForm.errors.description" />
                    </div>

                    <div class="flex items-center justify-between">
                        <PrimaryButton :disabled="moduleForm.processing">Save module</PrimaryButton>
                        <DangerButton type="button" @click="removeModule">Delete module</DangerButton>
                    </div>
                </form>

                <!-- Topics -->
                <div class="rounded-lg bg-white shadow-sm">
                    <h3 class="border-b border-gray-100 px-6 py-4 font-semibold text-gray-900">Topics</h3>

                    <div class="divide-y divide-gray-100">
                        <div v-for="topic in topics" :key="topic.id" class="px-6 py-4">
                            <TopicEditor :module-id="module.id" :topic="topic" />
                        </div>
                    </div>

                    <div class="border-t border-gray-100 bg-gray-50 px-6 py-4">
                        <p class="mb-3 text-sm font-medium text-gray-700">Add a topic</p>
                        <TopicEditor :module-id="module.id" />
                    </div>
                </div>

                <!-- Final work -->
                <form class="space-y-4 rounded-lg border-2 border-indigo-100 bg-white p-6 shadow-sm" @submit.prevent="saveTask">
                    <div>
                        <h3 class="font-semibold text-gray-900">Final work</h3>
                        <p class="text-sm text-gray-500">
                            The student sends a GitHub link and a message. The next module opens when the teacher accepts it.
                            Without a final work the module does not block the next one.
                        </p>
                    </div>

                    <div>
                        <InputLabel for="task_title" value="Title" />
                        <TextInput id="task_title" v-model="taskForm.title" type="text" class="mt-1 block w-full" />
                        <InputError class="mt-1" :message="taskForm.errors.title" />
                    </div>

                    <div>
                        <InputLabel for="task_instructions" value="Instructions" />
                        <textarea
                            id="task_instructions"
                            v-model="taskForm.instructions"
                            rows="5"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <InputError class="mt-1" :message="taskForm.errors.instructions" />
                    </div>

                    <div class="flex items-center justify-between">
                        <PrimaryButton :disabled="taskForm.processing">{{ task ? 'Save final work' : 'Create final work' }}</PrimaryButton>
                        <DangerButton v-if="task" type="button" @click="removeTask">Remove final work</DangerButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
