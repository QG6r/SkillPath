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
    <Head title="Course catalog" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Course catalog</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p v-if="courses.length === 0" class="rounded-lg bg-white p-6 text-center text-gray-600 shadow-sm">
                    No courses are published yet.
                </p>

                <div v-else class="grid gap-4 md:grid-cols-2">
                    <div v-for="course in courses" :key="course.id" class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="text-lg font-semibold text-gray-900">{{ course.title }}</h3>
                            <span
                                v-if="course.enrolled"
                                class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                            >
                                Joined
                            </span>
                        </div>
                        <p v-if="course.teacher" class="text-sm text-gray-500">Teacher: {{ course.teacher }}</p>
                        <p class="mt-2 text-sm text-gray-600">{{ course.description }}</p>
                        <p class="mt-2 text-xs text-gray-500">{{ course.modules_count }} modules</p>

                        <Link
                            :href="route('student.courses.show', course.id)"
                            class="mt-4 inline-block text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                        >
                            {{ course.enrolled ? 'Continue' : 'View course' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
