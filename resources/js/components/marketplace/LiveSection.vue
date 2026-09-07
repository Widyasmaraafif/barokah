<template>
    <section
        aria-label="Live shopping preview"
        class="mt-5 rounded-sm bg-white p-4 shadow-[var(--shadow-card)]"
    >
        <div
            class="flex min-h-[56px] items-center justify-between border-b border-[var(--border-soft)] pb-3"
        >
            <h2 class="text-base font-semibold text-[var(--text-primary)]">
                Live Shopping
            </h2>
            <span class="text-xs text-[var(--text-secondary)]">
                Lihat Semua &gt;
            </span>
        </div>
        <p class="mt-2 text-[11px] text-[var(--text-muted)]">
            Placeholder only. Live-streaming backend is TBC (spec §24 item 23).
        </p>
        <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
            <article
                v-for="n in 4"
                :key="n"
                class="w-[220px] shrink-0 overflow-hidden rounded-sm border border-[var(--border-soft)]"
            >
                <div
                    class="relative aspect-video bg-[var(--accent-navy)]"
                    aria-hidden="true"
                >
                    <span
                        class="absolute top-2 left-2 rounded-sm bg-[var(--accent-red)] px-1.5 py-0.5 text-[10px] font-bold text-white"
                    >
                        LIVE
                    </span>
                    <span
                        class="absolute bottom-2 left-2 rounded-sm bg-black/60 px-1.5 py-0.5 text-[10px] text-white"
                    >
                        1.2k watching
                    </span>
                </div>
                <p class="line-clamp-1 p-2 text-[12px]">
                    Seller live preview {{ n }}
                </p>
            </article>
        </div>
    </section>
</template>
