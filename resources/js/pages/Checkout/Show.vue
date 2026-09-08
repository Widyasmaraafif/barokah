<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { useCheckoutStore, type CheckoutStep } from '@/stores/checkout';
import { useSettingsStore } from '@/stores/settings';
import malaysiaStates from '@/data/malaysia-states.json';

const malaysiaStateOptions: string[] = (malaysiaStates as { name: string }[]).map(
    (stateOption) => stateOption.name,
);

type WizardProduct = {
    id: number;
    name: string;
    slug: string;
    price: string | number;
    stock: number;
};

const props = defineProps<{
    product: WizardProduct;
    profile: Record<string, string | null>;
}>();

const { formatAmount, loadSettings } = useSettingsStore();
const { state, setStep, setOrderNumber } = useCheckoutStore();

void loadSettings();

state.productId = props.product.id;
state.productSlug = props.product.slug;

if (state.step < 1 || state.step > 4) {
    setStep(1);
}

const buyer = reactive({
    name: state.buyer.name || props.profile.name || '',
    address: state.buyer.address || props.profile.address || '',
    state: state.buyer.state || props.profile.state || '',
    post_code: state.buyer.post_code || props.profile.post_code || '',
    phone: state.buyer.phone || props.profile.phone || '',
    email: state.buyer.email || props.profile.email || '',
});

const quantity = ref(state.quantity || 1);
const shippingMethod = ref(state.shippingMethod || 'fixed');
const paymentMethod = ref(state.paymentMethod || 'fpx');

const fieldErrors = ref<Record<string, string>>({});
const submitError = ref<string | null>(null);
const isSubmitting = ref(false);
const paymentStatus = ref<string | null>(null);
const paymentRedirectUrl = ref<string | null>(null);
const paymentQrPayload = ref<string | null>(null);
const isPolling = ref(false);

let pollTimer: ReturnType<typeof setInterval> | null = null;

const steps: { id: CheckoutStep; label: string }[] = [
    { id: 1, label: 'Buyer Information' },
    { id: 2, label: 'Shipping' },
    { id: 3, label: 'Payment' },
    { id: 4, label: 'Confirmation' },
];

const subtotal = computed(
    () => Number(props.product.price) * quantity.value,
);

function next(): void {
    if (state.step === 1 && !validateBuyer()) {
        return;
    }

    persistDraft();

    if (state.step < 3) {
        setStep((state.step + 1) as CheckoutStep);
    }
}

function back(): void {
    persistDraft();

    if (state.step > 1) {
        setStep((state.step - 1) as CheckoutStep);
    }
}

function validateBuyer(): boolean {
    const errors: Record<string, string> = {};

    if (buyer.name.trim() === '') {
        errors.name = 'Name is required.';
    }

    if (buyer.address.trim() === '') {
        errors.address = 'Address is required.';
    }

    if (buyer.state.trim() === '') {
        errors.state = 'State is required.';
    }

    if (buyer.post_code.trim() === '') {
        errors.post_code = 'Post code is required.';
    }

    if (buyer.phone.trim() === '') {
        errors.phone = 'Phone number is required.';
    }

    fieldErrors.value = errors;

    return Object.keys(errors).length === 0;
}

function persistDraft(): void {
    state.buyer = { ...buyer };
    state.quantity = quantity.value;
    state.shippingMethod = shippingMethod.value;
    state.paymentMethod = paymentMethod.value;
}

async function placeOrder(): Promise<void> {
    if (!validateBuyer()) {
        setStep(1);
        return;
    }

    persistDraft();
    isSubmitting.value = true;
    submitError.value = null;
    fieldErrors.value = {};

    try {
        const response = await fetch('/api/v1/orders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                product_id: props.product.id,
                quantity: quantity.value,
                buyer: {
                    name: buyer.name,
                    address: buyer.address,
                    state: buyer.state,
                    post_code: buyer.post_code,
                    phone: buyer.phone,
                    email: buyer.email || undefined,
                },
                shipping_method: shippingMethod.value,
            }),
        });

        const payload = (await response.json()) as {
            data?: { order_number?: string };
            message?: string;
            errors?: Record<string, string[]>;
        };

        if (response.status === 422 && payload.errors) {
            const flat: Record<string, string> = {};

            for (const [key, messages] of Object.entries(payload.errors)) {
                flat[key.replace(/^buyer\./, '')] = messages[0] ?? 'Invalid value.';
            }

            fieldErrors.value = flat;
            setStep(1);
            return;
        }

        if (!response.ok || !payload.data?.order_number) {
            submitError.value = payload.message ?? 'Order creation failed.';
            return;
        }

        setOrderNumber(payload.data.order_number);
        await initiatePayment(payload.data.order_number);
    } catch {
        submitError.value = 'Order creation failed. Please try again.';
    } finally {
        isSubmitting.value = false;
    }
}

async function initiatePayment(orderNumber: string): Promise<void> {
    const response = await fetch(`/api/v1/orders/${orderNumber}/payments`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
        body: JSON.stringify({ payment_method: paymentMethod.value }),
    });

    const payload = (await response.json()) as {
        data?: {
            status?: string;
            redirect_url?: string | null;
            qr_payload?: string | null;
        };
        message?: string;
    };

    if (!response.ok || !payload.data) {
        submitError.value = payload.message ?? 'Payment initiation failed.';
        return;
    }

    paymentStatus.value = payload.data.status ?? 'pending';
    paymentRedirectUrl.value = payload.data.redirect_url ?? null;
    paymentQrPayload.value = payload.data.qr_payload ?? null;
    setStep(4);
    startPolling(orderNumber);
    router.visit(`/checkout/confirmation/${orderNumber}`);
}

function startPolling(orderNumber: string): void {
    stopPolling();
    isPolling.value = true;

    pollTimer = setInterval(async () => {
        try {
            const response = await fetch(`/api/v1/orders/${orderNumber}/payment`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const payload = (await response.json()) as {
                data?: { status?: string };
            };

            const status = payload.data?.status ?? null;
            paymentStatus.value = status;

            if (status !== 'pending') {
                stopPolling();
            }
        } catch {
            // Keep polling; PayNet callbacks may still arrive.
        }
    }, 3000);
}

function stopPolling(): void {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }

    isPolling.value = false;
}
</script>

<template>
    <Head :title="`Checkout - ${product.name}`" />

    <div class="mx-auto w-full max-w-[1200px] px-4 py-6">
        <ol class="mb-6 flex flex-wrap gap-2 text-xs">
            <li
                v-for="step in steps"
                :key="step.id"
                :class="[
                    'rounded border px-3 py-2',
                    state.step === step.id
                        ? 'border-[var(--brand-primary)] font-semibold text-[var(--brand-primary)]'
                        : 'text-muted-foreground',
                ]"
            >
                {{ step.id }}. {{ step.label }}
            </li>
        </ol>

        <div class="grid gap-6 md:grid-cols-[1fr_320px]">
            <section class="rounded border bg-white p-4">
                <div v-if="state.step === 1">
                    <h1 class="text-lg font-semibold">Buyer Information</h1>
                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="mb-1 block text-sm" for="buyer-name">Name</label>
                            <input
                                id="buyer-name"
                                v-model="buyer.name"
                                type="text"
                                class="h-10 w-full rounded border px-3 text-sm"
                            />
                            <p v-if="fieldErrors.name" class="mt-1 text-xs text-red-600">
                                {{ fieldErrors.name }}
                            </p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm" for="buyer-address">Address</label>
                            <input
                                id="buyer-address"
                                v-model="buyer.address"
                                type="text"
                                class="h-10 w-full rounded border px-3 text-sm"
                            />
                            <p v-if="fieldErrors.address" class="mt-1 text-xs text-red-600">
                                {{ fieldErrors.address }}
                            </p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm" for="buyer-state">State</label>
                                <select
                                    id="buyer-state"
                                    v-model="buyer.state"
                                    class="h-10 w-full rounded border bg-white px-3 text-sm"
                                >
                                    <option value="" disabled>Select state</option>
                                    <option
                                        v-for="stateOption in malaysiaStateOptions"
                                        :key="stateOption"
                                        :value="stateOption"
                                    >
                                        {{ stateOption }}
                                    </option>
                                </select>
                                <p v-if="fieldErrors.state" class="mt-1 text-xs text-red-600">
                                    {{ fieldErrors.state }}
                                </p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm" for="buyer-postcode">Post Code</label>
                                <input
                                    id="buyer-postcode"
                                    v-model="buyer.post_code"
                                    type="text"
                                    class="h-10 w-full rounded border px-3 text-sm"
                                />
                                <p v-if="fieldErrors.post_code" class="mt-1 text-xs text-red-600">
                                    {{ fieldErrors.post_code }}
                                </p>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm" for="buyer-phone">Phone Number</label>
                                <input
                                    id="buyer-phone"
                                    v-model="buyer.phone"
                                    type="tel"
                                    class="h-10 w-full rounded border px-3 text-sm"
                                />
                                <p v-if="fieldErrors.phone" class="mt-1 text-xs text-red-600">
                                    {{ fieldErrors.phone }}
                                </p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm" for="buyer-email">Email (optional)</label>
                                <input
                                    id="buyer-email"
                                    v-model="buyer.email"
                                    type="email"
                                    class="h-10 w-full rounded border px-3 text-sm"
                                />
                                <p v-if="fieldErrors.email" class="mt-1 text-xs text-red-600">
                                    {{ fieldErrors.email }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm" for="checkout-quantity">Quantity</label>
                            <input
                                id="checkout-quantity"
                                v-model.number="quantity"
                                type="number"
                                min="1"
                                :max="product.stock"
                                class="h-10 w-32 rounded border px-3 text-sm"
                            />
                        </div>
                    </div>
                </div>

                <div v-else-if="state.step === 2">
                    <h1 class="text-lg font-semibold">Shipping</h1>
                    <label class="mt-4 flex items-center gap-2 text-sm">
                        <input v-model="shippingMethod" type="radio" value="fixed" />
                        Fixed rate shipping
                    </label>
                    <p class="mt-2 text-xs text-muted-foreground">
                        External shipping provider quote lands in Task 9 (spec §16).
                    </p>
                </div>

                <div v-else-if="state.step === 3">
                    <h1 class="text-lg font-semibold">Payment</h1>
                    <label class="mt-4 flex items-center gap-2 text-sm">
                        <input v-model="paymentMethod" type="radio" value="fpx" />
                        FPX
                    </label>
                    <label class="mt-2 flex items-center gap-2 text-sm">
                        <input v-model="paymentMethod" type="radio" value="duitnow" />
                        DuitNow
                    </label>
                    <p class="mt-2 text-xs text-muted-foreground">
                        Placing the order creates a pending_payment order, then
                        initiates a PayNet intent for the selected method
                        (TBC PayNet sandbox, spec §24 item 1).
                    </p>
                    <p v-if="submitError" class="mt-3 text-sm text-red-600">
                        {{ submitError }}
                    </p>
                </div>

                <div v-else>
                    <h1 class="text-lg font-semibold">Confirmation</h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Your order was created. Payment status:
                        {{ paymentStatus ?? 'pending' }}.
                    </p>
                    <p v-if="paymentRedirectUrl" class="mt-2 text-sm">
                        Continue to PayNet:
                        <a :href="paymentRedirectUrl" class="underline">Pay with FPX</a>
                        (TBC PayNet sandbox URL).
                    </p>
                    <p v-if="paymentQrPayload" class="mt-2 text-sm">
                        DuitNow QR reference: {{ paymentQrPayload }}
                    </p>
                    <p v-if="isPolling" class="mt-2 text-xs text-muted-foreground">
                        Checking payment status…
                    </p>
                </div>

                <div class="mt-6 flex gap-2">
                    <button
                        v-if="state.step > 1 && state.step < 4"
                        type="button"
                        class="h-11 rounded border px-5 text-sm"
                        @click="back"
                    >
                        Back
                    </button>
                    <button
                        v-if="state.step < 3"
                        type="button"
                        class="h-11 rounded bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white"
                        @click="next"
                    >
                        Continue
                    </button>
                    <button
                        v-if="state.step === 3"
                        type="button"
                        :disabled="isSubmitting"
                        class="h-11 rounded bg-[var(--brand-primary)] px-5 text-sm font-semibold text-white disabled:opacity-50"
                        @click="placeOrder"
                    >
                        {{ isSubmitting ? 'Placing order...' : 'Place Order' }}
                    </button>
                </div>
            </section>

            <aside class="h-fit rounded border bg-white p-4">
                <h2 class="text-sm font-semibold">{{ product.name }}</h2>
                <p class="mt-2 text-lg font-semibold text-[var(--brand-primary)]">
                    {{ formatAmount(Number(product.price)) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Quantity: {{ quantity }} · Subtotal:
                    {{ formatAmount(subtotal) }}
                </p>
            </aside>
        </div>
    </div>
</template>
