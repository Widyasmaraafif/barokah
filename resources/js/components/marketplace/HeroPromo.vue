<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';

const slides = [0, 1, 2];
const activeSlide = ref(0);
let timer: ReturnType<typeof setInterval> | null = null;

const sidePromos = [
    { title: 'New arrivals', subtitle: 'Fresh picks daily' },
    { title: 'Top categories', subtitle: 'Shop best sellers' },
];

const reduceMotion = computed(
    () =>
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches,
);

function goTo(index: number): void {
    activeSlide.value = (index + slides.length) % slides.length;
}

function startAutoplay(): void {
    if (reduceMotion.value || timer !== null) {
        return;
    }
    timer = setInterval(() => {
        goTo(activeSlide.value + 1);
    }, 5000);
}

function stopAutoplay(): void {
    if (timer !== null) {
        clearInterval(timer);
        timer = null;
    }
}

onMounted(() => {
    startAutoplay();
});

onUnmounted(() => {
    stopAutoplay();
});
</script>

<template>
    <section aria-label="Promotions" class="mt-4">
        <div class="grid gap-2 md:grid-cols-3">
            <div
                class="relative overflow-hidden rounded-sm bg-[var(--brand-primary-soft)] md:col-span-2"
                style="min-height: 240px"
            >
                <div
                    v-for="(slide, index) in slides"
                    :key="slide"
                    :class="[
                        'absolute inset-0 flex flex-col items-start justify-center gap-2 p-6 transition-opacity duration-500 md:p-10',
                        index === activeSlide
                            ? 'opacity-100'
                            : 'pointer-events-none opacity-0',
                    ]"
                    :aria-hidden="index !== activeSlide"
                >
                    <p
                        class="text-lg font-bold md:text-2xl"
                        style="color: var(--brand-primary)"
                    >
                        Barokah Marketplace Promo {{ index + 1 }}
                    </p>
                    <p class="text-sm text-[var(--text-secondary)]">
                        Static banner art. Admin-managed banners are TBC (spec
                        §24 item 27).
                    </p>
                </div>
                <div
                    class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5"
                >
                    <button
                        v-for="(slide, index) in slides"
                        :key="slide"
                        type="button"
                        :aria-label="`Go to banner ${index + 1}`"
                        :class="[
                            'h-2 rounded-full transition-all',
                            index === activeSlide
                                ? 'w-6 bg-[var(--brand-primary)]'
                                : 'w-2 bg-black/20',
                        ]"
                        @click="goTo(index)"
                    />
                </div>
            </div>
            <div class="hidden grid-rows-2 gap-2 md:grid">
                <div
                    v-for="promo in sidePromos"
                    :key="promo.title"
                    class="flex flex-col justify-center rounded-sm bg-[var(--bg-surface)] p-4 shadow-[var(--shadow-card)]"
                >
                    <p class="text-sm font-semibold">{{ promo.title }}</p>
                    <p class="text-xs text-[var(--text-muted)]">
                        {{ promo.subtitle }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
