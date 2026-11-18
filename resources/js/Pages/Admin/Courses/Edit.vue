<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    course: {
        type: Object,
        default: null,
    },
    modules: {
        type: Array,
        required: true,
    },
    teachers: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    title: props.course?.title ?? '',
    description: props.course?.description ?? '',
    teacher_id: props.course?.teacher_id ?? '',
    is_published: props.course?.is_published ?? false,
});

const moduleForm = useForm({
    title: '',
});

const save = () => {
    if (props.course) {
        form.put(route('admin.courses.update', props.course.id), { preserveScroll: true });
    } else {
        form.post(route('admin.courses.store'));
    }
};

const addModule = () => {
    moduleForm.post(route('admin.modules.store', props.course.id), {
        onSuccess: () => moduleForm.reset(),
    });
};

const removeCourse = () => {
    if (confirm('Delete this course with all its modules and the students\' submissions?')) {
        router.delete(route('admin.courses.destroy', props.course.id));
    }
};
</script>

<template>
    <Head :title="course ? course.title : 'New course'" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <Link :href="route('admin.courses.index')" class="text-sm text-indigo-600 hover:text-indigo-800">
                    &larr; Courses
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ course ? course.title : 'New course' }}
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <form class="space-y-4 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="save">
                    <div>
                        <InputLabel for="title" value="Title" />
                        <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.title" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>

                    <div>
                        <InputLabel for="teacher_id" value="Teacher who reviews the final works" />
                        <select
                            id="teacher_id"
                            v-model="form.teacher_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Not assigned</option>
                            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                                {{ teacher.name }} ({{ teacher.email }})
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.teacher_id" />
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input v-model="form.is_published" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        Visible to students in the catalog
                    </label>

                    <div class="flex items-center justify-between">
                        <PrimaryButton :disabled="form.processing">{{ course ? 'Save course' : 'Create course' }}</PrimaryButton>
                        <DangerButton v-if="course" type="button" @click="removeCourse">Delete course</DangerButton>
                    </div>
                </form>

                <!-- Modules exist only after the course is created -->
                <div v-if="course" class="rounded-lg bg-white shadow-sm">
                    <h3 class="border-b border-gray-100 px-6 py-4 font-semibold text-gray-900">Modules</h3>

                    <p v-if="modules.length === 0" class="px-6 py-4 text-sm text-gray-500">No modules yet.</p>

                    <ul v-else class="divide-y divide-gray-100">
                        <li v-for="module in modules" :key="module.id" class="flex items-center justify-between gap-4 px-6 py-4">
                            <div>
                                <p class="font-medium text-gray-900">{{ module.order }}. {{ module.title }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ module.topics_count }} topics &middot;
                                    <span :class="module.has_task ? 'text-green-700' : 'text-red-600'">
                                        {{ module.has_task ? 'final work set' : 'no final work' }}
                                    </span>
                                </p>
                            </div>
                            <Link
                                :href="route('admin.modules.edit', module.id)"
                                class="text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                            >
                                Edit content
                            </Link>
                        </li>
                    </ul>

                    <form class="flex items-start gap-3 border-t border-gray-100 px-6 py-4" @submit.prevent="addModule">
                        <div class="flex-1">
                            <TextInput v-model="moduleForm.title" type="text" placeholder="New module title" class="block w-full" />
                            <InputError class="mt-2" :message="moduleForm.errors.title" />
                        </div>
                        <PrimaryButton :disabled="moduleForm.processing">Add module</PrimaryButton>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
