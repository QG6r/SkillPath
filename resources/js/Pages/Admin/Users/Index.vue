<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
    roles: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'teacher',
});

const create = () => {
    form.post(route('admin.users.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('name', 'email', 'password'),
    });
};

const changeRole = (user, role) => {
    router.patch(route('admin.users.update', user.id), { role }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Users" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Users</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="user in users" :key="user.id">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ user.name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ user.email }}</td>
                                <td class="px-4 py-3">
                                    <select
                                        :value="user.role"
                                        :disabled="user.id === $page.props.auth.user.id"
                                        class="rounded-md border-gray-300 py-1 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                                        @change="changeRole(user, $event.target.value)"
                                    >
                                        <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <form class="space-y-4 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="create">
                    <div>
                        <h3 class="font-semibold text-gray-900">Create a user</h3>
                        <p class="text-sm text-gray-500">
                            Students register themselves. Create teachers and admins here.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="name" value="Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" />
                            <InputError class="mt-1" :message="form.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" />
                            <InputError class="mt-1" :message="form.errors.email" />
                        </div>
                        <div>
                            <InputLabel for="password" value="Password" />
                            <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" />
                            <InputError class="mt-1" :message="form.errors.password" />
                        </div>
                        <div>
                            <InputLabel for="role" value="Role" />
                            <select
                                id="role"
                                v-model="form.role"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.role" />
                        </div>
                    </div>

                    <PrimaryButton :disabled="form.processing">Create user</PrimaryButton>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
