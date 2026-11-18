<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { router, useForm } from '@inertiajs/vue3';

// Edits one topic of a module. Without a "topic" prop it is the form that adds a new topic.
const props = defineProps({
    moduleId: {
        type: Number,
        required: true,
    },
    topic: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    title: props.topic?.title ?? '',
    content: props.topic?.content ?? '',
    video_url: props.topic?.video_url ?? '',
    order: props.topic?.order ?? 1,
});

const save = () => {
    if (props.topic) {
        form.put(route('admin.topics.update', props.topic.id), { preserveScroll: true });
    } else {
        form.post(route('admin.topics.store', props.moduleId), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }
};

const remove = () => {
    if (confirm('Delete this topic?')) {
        router.delete(route('admin.topics.destroy', props.topic.id), { preserveScroll: true });
    }
};
</script>

<template>
    <form class="space-y-3" @submit.prevent="save">
        <div class="grid gap-3 sm:grid-cols-4">
            <div class="sm:col-span-3">
                <InputLabel value="Title" />
                <TextInput v-model="form.title" type="text" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="form.errors.title" />
            </div>
            <div v-if="topic">
                <InputLabel value="Position" />
                <input
                    v-model.number="form.order"
                    type="number"
                    min="1"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <InputError class="mt-1" :message="form.errors.order" />
            </div>
        </div>

        <div>
            <InputLabel value="Video link (YouTube or Vimeo, optional)" />
            <TextInput v-model="form.video_url" type="url" placeholder="https://www.youtube.com/watch?v=..." class="mt-1 block w-full" />
            <InputError class="mt-1" :message="form.errors.video_url" />
        </div>

        <div>
            <InputLabel value="Text (optional)" />
            <textarea
                v-model="form.content"
                rows="4"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            ></textarea>
            <InputError class="mt-1" :message="form.errors.content" />
        </div>

        <div class="flex items-center justify-between">
            <PrimaryButton :disabled="form.processing">{{ topic ? 'Save topic' : 'Add topic' }}</PrimaryButton>
            <DangerButton v-if="topic" type="button" @click="remove">Delete</DangerButton>
        </div>
    </form>
</template>
