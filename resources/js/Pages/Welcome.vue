<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="Welcome" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4">
        <div class="max-w-xl text-center">
            <h1 class="text-4xl font-bold text-gray-900">SkillPath</h1>
            <p class="mt-4 text-lg text-gray-600">
                Pick a course, work through its modules and hand in a final work for each one.
                A teacher checks it, and the next module opens when it is accepted.
            </p>

            <div class="mt-8 flex justify-center gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="rounded-md bg-gray-800 px-5 py-2 text-sm font-semibold text-white hover:bg-gray-700"
                >
                    Go to my page
                </Link>
                <template v-else>
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="rounded-md bg-gray-800 px-5 py-2 text-sm font-semibold text-white hover:bg-gray-700"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="rounded-md border border-gray-300 bg-white px-5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Register as a student
                    </Link>
                </template>
            </div>
        </div>
    </div>
</template>
