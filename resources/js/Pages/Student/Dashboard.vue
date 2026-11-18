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
    <Head title="My courses" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">My courses</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div v-if="courses.length === 0" class="rounded-lg bg-white p-6 text-center shadow-sm">
                    <p class="text-gray-600">You have not joined any course yet.</p>
                    <Link
                        :href="route('student.courses.index')"
                        class="mt-4 inline-block rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700"
                    >
                        Browse the course catalog
                    </Link>
                </div>

                <div v-else class="grid gap-4 md:grid-cols-2">
                    <div v-for="course in courses" :key="course.id" class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">{{ course.title }}</h3>
                        <p v-if="course.teacher" class="text-sm text-gray-500">Teacher: {{ course.teacher }}</p>

                        <div class="mt-4">
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>{{ course.modules_count }} modules</span>
                                <span>{{ course.progress }}% passed</span>
                            </div>
                            <div class="mt-1 h-2 rounded-full bg-gray-200">
                                <div class="h-2 rounded-full bg-green-500" :style="{ width: course.progress + '%' }"></div>
                            </div>
                        </div>

                        <Link
                            :href="route('student.courses.show', course.id)"
                            class="mt-4 inline-block text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                        >
                            Continue
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
