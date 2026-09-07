<template>
    <section
        aria-label="Quick services preview"
        class="mt-5 rounded-sm bg-white p-4 shadow-[var(--shadow-card)]"
    >
        <div
            class="flex min-h-[56px] items-center border-b border-[var(--border-soft)] pb-3"
        >
            <h2 class="text-base font-semibold text-[var(--text-primary)]">
                Quick Services
            </h2>
        </div>
        <p class="mt-2 text-[11px] text-[var(--text-muted)]">
            UI tiles only. Service destinations are TBC (spec §24) — no
            finance/pay-later/games backend.
        </p>
        <div
            class="mt-3 grid grid-cols-5 gap-2 md:grid-cols-8 lg:grid-cols-10"
        >
            <div
                v-for="service in services"
                :key="service"
                class="flex flex-col items-center gap-1.5 rounded-sm p-2 transition hover:-translate-y-0.5"
            >
                <span
                    class="flex h-11 w-11 items-center justify-center rounded-full bg-[var(--brand-primary-soft)] text-base font-bold md:h-[48px] md:w-[48px]"
                    style="color: var(--brand-primary)"
                    aria-hidden="true"
                >
                    {{ service.charAt(0) }}
                </span>
                <span
                    class="line-clamp-2 text-center text-[11px] text-[var(--text-secondary)] md:text-[12px]"
                >
                    {{ service }}
                </span>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
const services = [
    'Vouchers',
    'Top Up',
    'Bills',
    'Food',
    'Mall',
    'Live',
    'Games',
    'Finance',
    'Travel',
    'More',
];
</script>
