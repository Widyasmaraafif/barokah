<script setup lang="ts">
import { computed } from 'vue';
import { useSettingsStore } from '@/stores/settings';

const props = withDefaults(
    defineProps<{
        amount: number | string;
        originalAmount?: number | string | null;
    }>(),
    { originalAmount: null },
);

const { formatAmount } = useSettingsStore();

const formatted = computed(() => formatAmount(Number(props.amount)));

const formattedOriginal = computed(() =>
    props.originalAmount === null || props.originalAmount === undefined
        ? null
        : formatAmount(Number(props.originalAmount)),
);
</script>

<template>
    <span class="inline-flex flex-wrap items-baseline gap-x-2">
        <span class="text-base font-semibold text-[var(--brand-primary)]">
            {{ formatted }}
        </span>
        <s
            v-if="formattedOriginal !== null"
            class="text-xs text-[var(--text-muted)]"
        >
            {{ formattedOriginal }}
        </s>
    </span>
</template>
