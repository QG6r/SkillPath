<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    course: {
        type: Object,
        required: true,
    },
    enrolled: {
        type: Boolean,
        required: true,
    },
    progress: {
        type: Number,
        default: null,
    },
    modules: {
        type: Array,
        required: true,
    },
});

const join = () => router.post(route('student.courses.enroll', props.course.id));
</script>

<template>
    <Head :title="course.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ course.title }}</h2>
                <PrimaryButton v-if="!enrolled" type="button" @click="join">Join this course</PrimaryButton>
                <span v-else class="text-sm text-gray-500">{{ progress }}% passed</span>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p v-if="course.teacher" class="text-sm text-gray-500">Teacher: {{ course.teacher }}</p>
                    <p class="mt-2 whitespace-pre-line text-gray-700">{{ course.description }}</p>
                    <p v-if="!enrolled" class="mt-4 text-sm text-gray-500">
                        Joining is free. After you join, module 1 opens and the next ones open as your final works are accepted.
                    </p>
                </div>

                <div class="rounded-lg bg-white shadow-sm">
                    <h3 class="border-b border-gray-100 px-6 py-4 font-semibold text-gray-900">Modules</h3>

                    <p v-if="modules.length === 0" class="p-6 text-sm text-gray-500">This course has no modules yet.</p>

                    <ul v-else class="divide-y divide-gray-100">
                        <li v-for="(module, index) in modules" :key="module.id" class="flex items-center justify-between gap-4 px-6 py-4">
                            <div>
                                <p class="font-medium text-gray-900">{{ index + 1 }}. {{ module.title }}</p>
                                <p v-if="module.description" class="text-sm text-gray-500">{{ module.description }}</p>
                                <p class="text-xs text-gray-400">{{ module.topics_count }} topics</p>
                            </div>

                            <div class="flex shrink-0 items-center gap-3">
                                <StatusBadge v-if="module.state" :value="module.state" />
                                <Link
                                    v-if="module.state && module.state !== 'locked'"
                                    :href="route('student.modules.show', [course.id, module.id])"
                                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                                >
                                    Open
                                </Link>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
