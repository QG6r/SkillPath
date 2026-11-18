<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    courses: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <Head title="Courses" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Courses</h2>
                <Link
                    :href="route('admin.courses.create')"
                    class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700"
                >
                    New course
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <p v-if="courses.length === 0" class="p-6 text-center text-sm text-gray-500">
                        No courses yet. Create the first one.
                    </p>

                    <table v-else class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Course</th>
                                <th class="px-4 py-3">Teacher</th>
                                <th class="px-4 py-3">Modules</th>
                                <th class="px-4 py-3">Students</th>
                                <th class="px-4 py-3">Visible to students</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="course in courses" :key="course.id">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ course.title }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ course.teacher ?? 'not assigned' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ course.modules_count }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ course.students_count }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ course.is_published ? 'Yes' : 'Draft' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="route('admin.courses.edit', course.id)"
                                        class="font-semibold text-indigo-600 hover:text-indigo-800"
                                    >
                                        Edit
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
