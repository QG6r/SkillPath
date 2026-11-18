<script setup>
import { computed } from 'vue';

// One badge for both module states (locked, open, ...) and submission statuses (pending, correct, incorrect).
const props = defineProps({
    value: {
        type: String,
        required: true,
    },
});

const styles = {
    locked: ['Locked', 'bg-gray-100 text-gray-600'],
    open: ['Open', 'bg-blue-100 text-blue-800'],
    pending: ['Waiting for review', 'bg-yellow-100 text-yellow-800'],
    needs_fix: ['Needs changes', 'bg-red-100 text-red-800'],
    incorrect: ['Needs changes', 'bg-red-100 text-red-800'],
    passed: ['Passed', 'bg-green-100 text-green-800'],
    correct: ['Accepted', 'bg-green-100 text-green-800'],
};

const label = computed(() => (styles[props.value] ?? [props.value, 'bg-gray-100 text-gray-600'])[0]);
const classes = computed(() => (styles[props.value] ?? [props.value, 'bg-gray-100 text-gray-600'])[1]);
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="classes"
    >
        {{ label }}
    </span>
</template>
