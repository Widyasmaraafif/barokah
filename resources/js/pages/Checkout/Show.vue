<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Check, CreditCard, Lock, MapPin, Truck, User } from '@lucide/vue';
import { useCheckoutStore, type CheckoutStep } from '@/stores/checkout';
import { useCartStore } from '@/stores/cart';
import { useSettingsStore } from '@/stores/settings';
import { getMalaysiaCities } from '@/composables/useMalaysiaCities';
import malaysiaStates from '@/data/malaysia-states.json';
import MarketplaceLayout from '@/layouts/MarketplaceLayout.vue';

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

type ProductProp = WizardProduct | { data: WizardProduct };

type CartCheckoutItem = {
    productId: number;
    name: string;
    price: number;
    quantity: number;
};

const props = defineProps<{
    product: ProductProp | null;
    profile: Record<string, string | null>;
    cartCheckout: boolean;
    initialQuantity?: number;
}>();

const { formatAmount, getSettingValue, loadSettings } = useSettingsStore();
const { state, setStep, setOrderNumber } = useCheckoutStore();
const { state: cartState, clear: clearCart } = useCartStore();

const product = computed<WizardProduct | null>(() => {
    if (!props.product) {
        return null;
    }

    return 'data' in props.product ? props.product.data : props.product;
});

void loadSettings();

if (!props.cartCheckout && props.product) {
    state.productId = product.value.id;
    state.productSlug = product.value.slug;
}

if (state.step < 1 || state.step > 4) {
    setStep(1);
}

const buyer = reactive({
    name: state.buyer.name || props.profile.name || '',
    address: state.buyer.address || props.profile.address || '',
    state: state.buyer.state || props.profile.state || '',
    city: state.buyer.city || props.profile.city || '',
    post_code: state.buyer.post_code || props.profile.post_code || '',
    phone: state.buyer.phone || props.profile.phone || '',
    email: state.buyer.email || props.profile.email || '',
    shipping_address: state.buyer.shipping_address || '',
    shipping_state: state.buyer.shipping_state || '',
    shipping_city: state.buyer.shipping_city || '',
    shipping_post_code: state.buyer.shipping_post_code || '',
});

const sameAsBuyer = ref(
    buyer.shipping_address === '' &&
        buyer.shipping_state === '' &&
        buyer.shipping_post_code === '',
);

function applySameAsBuyer(): void {
    if (sameAsBuyer.value) {
        buyer.shipping_address = buyer.address;
        buyer.shipping_state = buyer.state;
        buyer.shipping_city = buyer.city;
        buyer.shipping_post_code = buyer.post_code;
    }
}

watch(sameAsBuyer, () => {
    applySameAsBuyer();
});

watch(
    () => [buyer.address, buyer.state, buyer.city, buyer.post_code],
    () => {
        if (sameAsBuyer.value) {
            applySameAsBuyer();
        }
    },
);

if (sameAsBuyer.value) {
    applySameAsBuyer();
}

const cityOptions = computed(() =>
    buyer.state === '' ? [] : getMalaysiaCities(buyer.state),
);

const shippingCityOptions = computed(() =>
    buyer.shipping_state === '' ? [] : getMalaysiaCities(buyer.shipping_state),
);

watch(() => buyer.state, (nextState, prevState) => {
    if (nextState !== prevState) {
        buyer.city = '';
    }
});
watch(() => buyer.shipping_state, (nextState, prevState) => {
    if (nextState !== prevState) {
        buyer.shipping_city = '';
    }
});

const quantity = ref(props.initialQuantity ?? state.quantity ?? 1);
const shippingMethod = ref(state.shippingMethod || 'fixed');
const shippingFee = ref<number | null>(null);
const isLoadingShipping = ref(false);
const shippingError = ref<string | null>(null);
const cartItems = computed<CartCheckoutItem[]>(() =>
    cartState.items.map((item) => ({
        productId: item.productId,
        name: item.name,
        price: item.price,
        quantity: item.quantity,
    })),
);

const checkoutSubtotal = computed(() =>
    props.cartCheckout
        ? cartItems.value.reduce(
              (total, item) => total + item.price * item.quantity,
              0,
          )
        : Number(product.value?.price ?? 0) * quantity.value,
);

type PaymentOption = {
    value: string;
    label: string;
    hint: string;
};

const bankTransferEnabled = computed(() => getSettingValue<boolean>('payment.bank_transfer_enabled', true));
const qrCodeEnabled = computed(() => getSettingValue<boolean>('payment.qr_code_enabled', true));
const paynetEnabled = computed(() => getSettingValue<boolean>('payment.paynet_enabled', true));
const fpxEnabled = computed(() => getSettingValue<boolean>('payment.fpx_enabled', true));
const duitnowEnabled = computed(() => getSettingValue<boolean>('payment.duitnow_enabled', true));
const bankName = computed(() => getSettingValue<string>('payment.bank_name', ''));
const bankAccountName = computed(() => getSettingValue<string>('payment.bank_account_name', ''));
const bankAccountNumber = computed(() => getSettingValue<string>('payment.bank_account_number', ''));
const qrCodeUrl = computed(() => getSettingValue<string>('payment.qr_code_url', ''));

const paymentOptions = computed<PaymentOption[]>(() => [...manualOptions.value, ...paynetOptions.value]);

const manualOptions = computed<PaymentOption[]>(() => {
    const options: PaymentOption[] = [];

    if (bankTransferEnabled.value) {
        options.push({
            value: 'bank_transfer',
            label: 'Bank Transfer',
            hint: 'Transfer manually, then wait for admin verification',
        });
    }

    if (qrCodeEnabled.value) {
        options.push({
            value: 'qr_code',
            label: 'QR Code',
            hint: 'Scan the static QR code, then wait for admin verification',
        });
    }

    return options;
});

const paynetOptions = computed<PaymentOption[]>(() => {
    const options: PaymentOption[] = [];

    if (!paynetEnabled.value) {
        return options;
    }

    if (fpxEnabled.value) {
        options.push({
            value: 'fpx',
            label: 'FPX',
            hint: 'Online banking transfer via PayNet',
        });
    }

    if (duitnowEnabled.value) {
        options.push({
            value: 'duitnow',
            label: 'DuitNow QR',
            hint: 'Scan a QR code with any banking app',
        });
    }

    return options;
});

const paymentMethod = ref(state.paymentMethod || 'fpx');

const isPaynetSelected = computed(() => paynetOptions.value.some((option) => option.value === paymentMethod.value));

const selectedPaymentOption = computed<PaymentOption | null>(
    () => paymentOptions.value.find((option) => option.value === paymentMethod.value) ?? null,
);

const selectedPaymentLabel = computed(
    () => selectedPaymentOption.value?.label ?? paymentMethod.value,
);

const buyerFullAddress = computed(() =>
    [buyer.address, buyer.city, buyer.state, buyer.post_code].filter((part) => part !== '').join(', '),
);

const shippingFullAddress = computed(() =>
    [buyer.shipping_address, buyer.shipping_city, buyer.shipping_state, buyer.shipping_post_code]
        .filter((part) => part !== '')
        .join(', '),
);

watch(paymentOptions, (options) => {
    if (options.length > 0 && !options.some((option) => option.value === paymentMethod.value)) {
        paymentMethod.value = options[0]?.value ?? 'fpx';
        persistDraft();
    }
});

onMounted(() => {
    if (paymentOptions.value.length > 0 && !paymentOptions.value.some((option) => option.value === paymentMethod.value)) {
        paymentMethod.value = paymentOptions.value[0]?.value ?? 'fpx';
        persistDraft();
    }
});

const fieldErrors = ref<Record<string, string>>({});
const submitError = ref<string | null>(null);
const isSubmitting = ref(false);
const createdOrderNumber = ref<string | null>(null);
const paymentStatus = ref<string | null>(null);
const paymentRedirectUrl = ref<string | null>(null);
const paymentQrPayload = ref<string | null>(null);
const isPolling = ref(false);

let pollTimer: ReturnType<typeof setInterval> | null = null;

const steps = [
    { id: 1 as CheckoutStep, label: 'Buyer', hint: 'Contact' },
    { id: 2 as CheckoutStep, label: 'Shipping', hint: 'Address' },
    { id: 3 as CheckoutStep, label: 'Payment', hint: 'Method' },
    { id: 4 as CheckoutStep, label: 'Review', hint: 'Confirm' },
];

const subtotal = computed(() => checkoutSubtotal.value);
const orderTotal = computed(() => subtotal.value + (shippingFee.value ?? 0));
const itemCount = computed(() =>
    props.cartCheckout
        ? cartItems.value.reduce((total, item) => total + item.quantity, 0)
        : quantity.value,
);

function incrementQuantity(): void {
    const max = product.value?.stock ?? 999;
    if (quantity.value < max) {
        quantity.value += 1;
    }
}

function decrementQuantity(): void {
    if (quantity.value > 1) {
        quantity.value -= 1;
    }
}

async function next(): Promise<void> {
    if (state.step === 1 && !validatePersonal()) {
        return;
    }

    if (state.step === 2) {
        if (!validateShipping()) {
            return;
        }

        await loadShippingQuote();
        if (shippingError.value) {
            return;
        }
    }

    if (state.step === 3) {
        if (paymentOptions.value.length === 0) {
            submitError.value = 'No payment method is enabled right now.';
            return;
        }

        if (!paymentOptions.value.some((option) => option.value === paymentMethod.value)) {
            submitError.value = 'Selected payment method is not available.';
            return;
        }

        if (shippingFee.value === null) {
            await loadShippingQuote();
            if (shippingError.value || shippingFee.value === null) {
                setStep(2);
                return;
            }
        }

        submitError.value = null;
    }

    persistDraft();

    if (state.step < 4) {
        setStep((state.step + 1) as CheckoutStep);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function back(): void {
    persistDraft();

    if (state.step > 1) {
        setStep((state.step - 1) as CheckoutStep);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function validatePersonal(): boolean {
    const errors: Record<string, string> = {};

    if (buyer.name.trim() === '') {
        errors.name = 'Name is required.';
    }

    if (buyer.phone.trim() === '') {
        errors.phone = 'Phone number is required.';
    }

    if (buyer.email.trim() !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(buyer.email.trim())) {
        errors.email = 'Enter a valid email address.';
    }

    if (!props.cartCheckout) {
        const max = product.value?.stock ?? 1;
        if (!Number.isInteger(quantity.value) || quantity.value < 1) {
            errors.quantity = 'Quantity must be at least 1.';
        } else if (quantity.value > max) {
            errors.quantity = `Only ${max} available.`;
        }
    }

    fieldErrors.value = errors;

    return Object.keys(errors).length === 0;
}

function validateShipping(): boolean {
    const errors: Record<string, string> = {};

    if (buyer.address.trim() === '') {
        errors.address = 'Billing address is required.';
    }

    if (buyer.state.trim() === '') {
        errors.state = 'State is required.';
    }

    if (buyer.post_code.trim() === '') {
        errors.post_code = 'Post code is required.';
    }

    if (buyer.shipping_address.trim() === '') {
        errors.shipping_address = 'Delivery address is required.';
    }

    if (buyer.shipping_state.trim() === '') {
        errors.shipping_state = 'Delivery state is required.';
    }

    if (buyer.shipping_post_code.trim() === '') {
        errors.shipping_post_code = 'Delivery post code is required.';
    }

    fieldErrors.value = errors;

    return Object.keys(errors).length === 0;
}

function validateBuyer(): boolean {
    const personalErrors: Record<string, string> = {};
    const shippingErrors: Record<string, string> = {};

    if (buyer.name.trim() === '') {
        personalErrors.name = 'Name is required.';
    }

    if (buyer.phone.trim() === '') {
        personalErrors.phone = 'Phone number is required.';
    }

    if (buyer.address.trim() === '') {
        shippingErrors.address = 'Billing address is required.';
    }

    if (buyer.state.trim() === '') {
        shippingErrors.state = 'State is required.';
    }

    if (buyer.post_code.trim() === '') {
        shippingErrors.post_code = 'Post code is required.';
    }

    if (buyer.shipping_address.trim() === '') {
        shippingErrors.shipping_address = 'Delivery address is required.';
    }

    if (buyer.shipping_state.trim() === '') {
        shippingErrors.shipping_state = 'Delivery state is required.';
    }

    if (buyer.shipping_post_code.trim() === '') {
        shippingErrors.shipping_post_code = 'Delivery post code is required.';
    }

    fieldErrors.value = { ...personalErrors, ...shippingErrors };

    return Object.keys(fieldErrors.value).length === 0;
}

async function loadShippingQuote(): Promise<void> {
    if (!validateShipping()) {
        return;
    }

    isLoadingShipping.value = true;
    shippingError.value = null;

    try {
        const response = await fetch('/api/v1/shipping/quote', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({
                address: buyer.shipping_address,
                state: buyer.shipping_state,
                city: buyer.shipping_city || null,
                post_code: buyer.shipping_post_code,
                method: 'fixed',
                subtotal: checkoutSubtotal.value,
                items: props.cartCheckout
                    ? cartItems.value.map((item) => ({ product_id: item.productId, quantity: item.quantity }))
                    : [{ product_id: product.value?.id, quantity: quantity.value }],
            }),
        });
        const payload = (await response.json()) as { data?: { fee?: number }; message?: string };
        if (!response.ok) {
            shippingError.value = payload.message ?? 'Shipping quote failed.';
            return;
        }
        shippingFee.value = payload.data?.fee ?? 0;
    } catch {
        shippingError.value = 'Shipping quote failed. Please try again.';
    } finally {
        isLoadingShipping.value = false;
    }
}

function persistDraft(): void {
    state.buyer = { ...buyer };
    state.quantity = quantity.value;
    state.shippingMethod = shippingMethod.value;
    state.paymentMethod = paymentMethod.value;
}

async function placeOrder(): Promise<void> {
    if (createdOrderNumber.value) {
        await initiatePayment(createdOrderNumber.value);
        return;
    }

    if (props.cartCheckout && cartItems.value.length === 0) {
        submitError.value = 'Your cart is empty.';
        return;
    }

    if (!validateBuyer()) {
        setStep(buyer.name.trim() === '' || buyer.phone.trim() === '' ? 1 : 2);
        return;
    }

    if (paymentOptions.value.length === 0) {
        submitError.value = 'No payment method is enabled right now.';
        return;
    }

    if (!paymentOptions.value.some((option) => option.value === paymentMethod.value)) {
        submitError.value = 'Selected payment method is not available.';
        return;
    }

    if (shippingFee.value === null) {
        await loadShippingQuote();
        if (shippingError.value || shippingFee.value === null) {
            setStep(2);
            return;
        }
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
                ...(props.cartCheckout
                    ? {
                          items: cartItems.value.map((item) => ({
                              product_id: item.productId,
                              quantity: item.quantity,
                          })),
                      }
                    : {
                          product_id: product.value?.id,
                          quantity: quantity.value,
                      }),
                buyer: {
                    name: buyer.name,
                    address: buyer.address,
                    state: buyer.state,
                    city: buyer.city === '' ? undefined : buyer.city,
                    post_code: buyer.post_code,
                    phone: buyer.phone,
                    email: buyer.email || undefined,
                },
                shipping_address: buyer.shipping_address,
                shipping_state: buyer.shipping_state,
                shipping_city: buyer.shipping_city === '' ? undefined : buyer.shipping_city,
                shipping_post_code: buyer.shipping_post_code,
                shipping_method: shippingMethod.value,
            }),
        });

        let payload: {
            data?: { order_number?: string };
            message?: string;
            errors?: Record<string, string[]>;
        };

        try {
            payload = (await response.json()) as {
                data?: { order_number?: string };
                message?: string;
                errors?: Record<string, string[]>;
            };
        } catch {
            submitError.value = `Order request failed (HTTP ${response.status}).`;
            return;
        }

        if (response.status === 422 && payload.errors) {
            const flat: Record<string, string> = {};

            for (const [key, messages] of Object.entries(payload.errors)) {
                flat[key.replace(/^buyer\./, '')] = messages[0] ?? 'Invalid value.';
            }

            fieldErrors.value = flat;
            setStep(flat.name || flat.phone ? 1 : 2);
            return;
        }

        if (!response.ok || !payload.data?.order_number) {
            submitError.value = payload.message ?? `Order creation failed (HTTP ${response.status}).`;
            return;
        }

        createdOrderNumber.value = payload.data.order_number;
        setOrderNumber(payload.data.order_number);
        if (props.cartCheckout) {
            clearCart();
        }
        await initiatePayment(payload.data.order_number);
    } catch (error) {
        submitError.value = error instanceof Error ? error.message : 'Order creation failed. Please try again.';
    } finally {
        isSubmitting.value = false;
    }
}

async function initiatePayment(orderNumber: string): Promise<void> {
    try {
        const response = await fetch(`/api/v1/orders/${orderNumber}/payments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({ payment_method: paymentMethod.value }),
        });

        let payload: {
            data?: {
                status?: string;
                redirect_url?: string | null;
                qr_payload?: string | null;
            };
            message?: string;
        };

        try {
            payload = (await response.json()) as {
                data?: {
                    status?: string;
                    redirect_url?: string | null;
                    qr_payload?: string | null;
                };
                message?: string;
            };
        } catch {
            submitError.value = `Payment initiation failed (HTTP ${response.status}). Order ${orderNumber} was created.`;
            setStep(4);
            return;
        }

        if (!response.ok || !payload.data) {
            submitError.value = payload.message ?? `Payment initiation failed (HTTP ${response.status}). Order ${orderNumber} was created.`;
            setStep(4);
            return;
        }

        paymentStatus.value = payload.data.status ?? 'pending';
        paymentRedirectUrl.value = payload.data.redirect_url ?? null;
        paymentQrPayload.value = payload.data.qr_payload ?? null;
        setStep(4);

        if (isPaynetSelected.value) {
            startPolling(orderNumber);
        }

        router.visit(`/checkout/confirmation/${orderNumber}?payment_method=${paymentMethod.value}`);
    } catch (error) {
        submitError.value = error instanceof Error ? error.message : 'Payment initiation failed. Please try again.';
        setStep(4);
    }
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

onBeforeUnmount(() => {
    stopPolling();
});

const inputClass =
    'h-11 w-full rounded-sm border border-[var(--border-default)] bg-white px-3 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-faint)] outline-none transition focus:border-[var(--brand-primary)] focus:ring-2 focus:ring-[var(--brand-primary)]/15 disabled:cursor-not-allowed disabled:bg-[var(--bg-muted)] disabled:opacity-60';
const labelClass = 'mb-1.5 block text-sm font-medium text-[var(--text-primary)]';
const errorClass = 'mt-1 text-xs text-red-600';
const sectionTitleClass = 'text-base font-semibold text-[var(--text-primary)]';
const sectionHintClass = 'mt-1 text-sm text-[var(--text-muted)]';
</script>

<template>
    <MarketplaceLayout>
        <Head :title="cartCheckout ? 'Cart checkout' : `Checkout - ${product?.name ?? ''}`" />

        <div class="mx-auto w-full max-w-[1200px] px-4 py-6 pb-24 md:pb-8">
            <nav class="mb-3 flex items-center gap-1.5 text-xs text-[var(--text-muted)]">
                <Link href="/" class="hover:underline">Home</Link>
                <span>/</span>
                <Link v-if="cartCheckout" href="/cart" class="hover:underline">Cart</Link>
                <Link v-else-if="product" :href="`/products/${product.slug}`" class="max-w-40 truncate hover:underline">
                    {{ product.name }}
                </Link>
                <span>/</span>
                <span class="font-medium text-[var(--text-primary)]">Checkout</span>
            </nav>

            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-[var(--text-primary)] md:text-2xl">
                        {{ cartCheckout ? 'Cart checkout' : 'Checkout' }}
                    </h1>
                    <p class="mt-1 text-sm text-[var(--text-muted)]">
                        {{ itemCount }} item{{ itemCount === 1 ? '' : 's' }} · secure checkout powered by PayNet
                    </p>
                </div>
                <p class="flex items-center gap-1.5 text-xs text-[var(--text-muted)]">
                    <Lock class="size-3.5" aria-hidden="true" />
                    Orders expire in 30 minutes if unpaid
                </p>
            </div>

            <ol class="mt-5 grid grid-cols-4 overflow-hidden rounded-sm border border-[var(--border-default)] bg-white">
                <li
                    v-for="(step, index) in steps"
                    :key="step.id"
                    :class="[
                        'relative flex items-center gap-2.5 px-3 py-3 md:px-4',
                        index > 0 ? 'border-l border-[var(--border-soft)]' : '',
                        state.step === step.id ? 'bg-[var(--brand-primary-soft)]' : '',
                    ]"
                >
                    <span
                        :class="[
                            'flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                            state.step > step.id
                                ? 'bg-[var(--accent-cyan)] text-white'
                                : state.step === step.id
                                  ? 'bg-[var(--brand-primary)] text-white'
                                  : 'bg-[var(--bg-muted)] text-[var(--text-muted)]',
                        ]"
                    >
                        <Check v-if="state.step > step.id" class="size-3.5" aria-hidden="true" />
                        <template v-else>{{ step.id }}</template>
                    </span>
                    <span class="min-w-0">
                        <span
                            :class="[
                                'block truncate text-xs font-semibold md:text-sm',
                                state.step === step.id ? 'text-[var(--brand-primary)]' : 'text-[var(--text-primary)]',
                            ]"
                        >
                            {{ step.label }}
                        </span>
                        <span class="hidden truncate text-[11px] text-[var(--text-muted)] sm:block">
                            {{ step.hint }}
                        </span>
                    </span>
                </li>
            </ol>

            <div class="mt-5 grid items-start gap-5 lg:grid-cols-[1fr_320px]">
                <section class="rounded-sm border border-[var(--border-default)] bg-white p-4 md:p-6">
                    <div v-if="state.step === 1">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-9 items-center justify-center rounded-sm bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]">
                                <User class="size-4.5" aria-hidden="true" />
                            </span>
                            <div>
                                <h2 :class="sectionTitleClass">Buyer information</h2>
                                <p :class="sectionHintClass">We use this to contact you about the order.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label :class="labelClass" for="buyer-name">Full name *</label>
                                <input
                                    id="buyer-name"
                                    v-model="buyer.name"
                                    type="text"
                                    autocomplete="name"
                                    placeholder="e.g. Ahmad bin Ali"
                                    :class="inputClass"
                                />
                                <p v-if="fieldErrors.name" :class="errorClass">{{ fieldErrors.name }}</p>
                            </div>
                            <div>
                                <label :class="labelClass" for="buyer-phone">Phone number *</label>
                                <input
                                    id="buyer-phone"
                                    v-model="buyer.phone"
                                    type="tel"
                                    autocomplete="tel"
                                    placeholder="e.g. 0123456789"
                                    :class="inputClass"
                                />
                                <p v-if="fieldErrors.phone" :class="errorClass">{{ fieldErrors.phone }}</p>
                            </div>
                            <div>
                                <label :class="labelClass" for="buyer-email">Email <span class="font-normal text-[var(--text-muted)]">(optional)</span></label>
                                <input
                                    id="buyer-email"
                                    v-model="buyer.email"
                                    type="email"
                                    autocomplete="email"
                                    placeholder="you@example.com"
                                    :class="inputClass"
                                />
                                <p v-if="fieldErrors.email" :class="errorClass">{{ fieldErrors.email }}</p>
                            </div>
                        </div>

                        <div v-if="!cartCheckout && product" class="mt-5 rounded-sm border border-[var(--border-soft)] bg-[var(--bg-muted)] p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-[var(--text-primary)]">{{ product.name }}</p>
                                    <p class="mt-0.5 text-xs text-[var(--text-muted)]">
                                        {{ formatAmount(Number(product.price)) }} each · {{ product.stock }} available
                                    </p>
                                </div>
                                <div class="inline-flex h-11 items-center overflow-hidden rounded-sm border border-[var(--border-default)] bg-white">
                                    <button
                                        type="button"
                                        class="flex h-full w-10 items-center justify-center text-lg text-[var(--text-secondary)] hover:bg-[var(--bg-muted)] disabled:opacity-40"
                                        aria-label="Decrease quantity"
                                        :disabled="quantity <= 1"
                                        @click="decrementQuantity"
                                    >
                                        −
                                    </button>
                                    <span class="w-12 text-center text-sm font-semibold">{{ quantity }}</span>
                                    <button
                                        type="button"
                                        class="flex h-full w-10 items-center justify-center text-lg text-[var(--text-secondary)] hover:bg-[var(--bg-muted)] disabled:opacity-40"
                                        aria-label="Increase quantity"
                                        :disabled="quantity >= product.stock"
                                        @click="incrementQuantity"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>
                            <p v-if="fieldErrors.quantity" :class="errorClass">{{ fieldErrors.quantity }}</p>
                        </div>

                        <div v-if="cartCheckout" class="mt-5 overflow-hidden rounded-sm border border-[var(--border-soft)]">
                            <p class="border-b border-[var(--border-soft)] bg-[var(--bg-muted)] px-4 py-2.5 text-sm font-semibold">
                                Cart items ({{ cartItems.length }})
                            </p>
                            <ul class="divide-y divide-[var(--border-soft)]">
                                <li
                                    v-for="item in cartItems"
                                    :key="item.productId"
                                    class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm"
                                >
                                    <span class="min-w-0 truncate text-[var(--text-primary)]">
                                        {{ item.name }}
                                        <span class="text-[var(--text-muted)]">× {{ item.quantity }}</span>
                                    </span>
                                    <span class="shrink-0 font-medium">{{ formatAmount(item.price * item.quantity) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div v-else-if="state.step === 2">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-9 items-center justify-center rounded-sm bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]">
                                <MapPin class="size-4.5" aria-hidden="true" />
                            </span>
                            <div>
                                <h2 :class="sectionTitleClass">Shipping details</h2>
                                <p :class="sectionHintClass">Billing address is kept for the receipt; delivery address sets the shipping fee.</p>
                            </div>
                        </div>

                        <label class="mt-5 flex cursor-pointer items-center gap-2.5 rounded-sm border border-[var(--border-soft)] bg-[var(--bg-muted)] px-3.5 py-3 text-sm">
                            <input v-model="sameAsBuyer" type="checkbox" class="size-4 accent-[var(--brand-primary)]" />
                            <span class="font-medium text-[var(--text-primary)]">Deliver to my billing address</span>
                        </label>

                        <div class="mt-5">
                            <h3 class="text-sm font-semibold text-[var(--text-primary)]">Billing address</h3>
                            <div class="mt-3 grid gap-4">
                                <div>
                                    <label :class="labelClass" for="buyer-address">Street address *</label>
                                    <input
                                        id="buyer-address"
                                        v-model="buyer.address"
                                        type="text"
                                        autocomplete="street-address"
                                        placeholder="No. 1, Jalan Merdeka"
                                        :class="inputClass"
                                    />
                                    <p v-if="fieldErrors.address" :class="errorClass">{{ fieldErrors.address }}</p>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div>
                                        <label :class="labelClass" for="buyer-state">State *</label>
                                        <select id="buyer-state" v-model="buyer.state" :class="inputClass">
                                            <option value="" disabled>Select state</option>
                                            <option v-for="stateOption in malaysiaStateOptions" :key="stateOption" :value="stateOption">
                                                {{ stateOption }}
                                            </option>
                                        </select>
                                        <p v-if="fieldErrors.state" :class="errorClass">{{ fieldErrors.state }}</p>
                                    </div>
                                    <div>
                                        <label :class="labelClass" for="buyer-city">City</label>
                                        <select id="buyer-city" v-model="buyer.city" :disabled="buyer.state === ''" :class="inputClass">
                                            <option value="">
                                                {{ buyer.state === '' ? 'Select state first' : cityOptions.length === 0 ? 'No cities listed' : 'Select city' }}
                                            </option>
                                            <option v-for="cityOption in cityOptions" :key="cityOption" :value="cityOption">
                                                {{ cityOption }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label :class="labelClass" for="buyer-postcode">Post code *</label>
                                        <input
                                            id="buyer-postcode"
                                            v-model="buyer.post_code"
                                            type="text"
                                            inputmode="numeric"
                                            placeholder="40000"
                                            :class="inputClass"
                                        />
                                        <p v-if="fieldErrors.post_code" :class="errorClass">{{ fieldErrors.post_code }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 border-t border-[var(--border-soft)] pt-5">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-[var(--text-primary)]">Delivery address</h3>
                                <span v-if="buyer.shipping_state || buyer.shipping_city" class="text-xs text-[var(--text-muted)]">
                                    {{ [buyer.shipping_city, buyer.shipping_state].filter(Boolean).join(', ') }}
                                </span>
                            </div>
                            <div class="mt-3 grid gap-4" :class="sameAsBuyer ? 'pointer-events-none opacity-60' : ''">
                                <div>
                                    <label :class="labelClass" for="shipping-address">Street address *</label>
                                    <input
                                        id="shipping-address"
                                        v-model="buyer.shipping_address"
                                        type="text"
                                        placeholder="No. 99, Jalan Tujuan"
                                        :class="inputClass"
                                        :disabled="sameAsBuyer"
                                    />
                                    <p v-if="fieldErrors.shipping_address" :class="errorClass">{{ fieldErrors.shipping_address }}</p>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div>
                                        <label :class="labelClass" for="shipping-state">State *</label>
                                        <select id="shipping-state" v-model="buyer.shipping_state" :class="inputClass" :disabled="sameAsBuyer">
                                            <option value="" disabled>Select state</option>
                                            <option v-for="stateOption in malaysiaStateOptions" :key="stateOption" :value="stateOption">
                                                {{ stateOption }}
                                            </option>
                                        </select>
                                        <p v-if="fieldErrors.shipping_state" :class="errorClass">{{ fieldErrors.shipping_state }}</p>
                                    </div>
                                    <div>
                                        <label :class="labelClass" for="shipping-city">City</label>
                                        <select
                                            id="shipping-city"
                                            v-model="buyer.shipping_city"
                                            :disabled="sameAsBuyer || buyer.shipping_state === ''"
                                            :class="inputClass"
                                        >
                                            <option value="">Select city</option>
                                            <option v-for="cityOption in shippingCityOptions" :key="cityOption" :value="cityOption">
                                                {{ cityOption }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label :class="labelClass" for="shipping-postcode">Post code *</label>
                                        <input
                                            id="shipping-postcode"
                                            v-model="buyer.shipping_post_code"
                                            type="text"
                                            inputmode="numeric"
                                            placeholder="46000"
                                            :class="inputClass"
                                            :disabled="sameAsBuyer"
                                        />
                                        <p v-if="fieldErrors.shipping_post_code" :class="errorClass">{{ fieldErrors.shipping_post_code }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <h3 class="text-sm font-semibold text-[var(--text-primary)]">Shipping method</h3>
                            <label
                                class="mt-3 flex cursor-pointer items-center gap-3 rounded-sm border-2 border-[var(--brand-primary)] bg-[var(--brand-primary-soft)] px-4 py-3"
                            >
                                <input v-model="shippingMethod" type="radio" value="fixed" class="size-4 accent-[var(--brand-primary)]" />
                                <span class="flex-1">
                                    <span class="flex items-center gap-1.5 text-sm font-semibold text-[var(--text-primary)]">
                                        <Truck class="size-4" aria-hidden="true" />
                                        Fixed rate shipping
                                    </span>
                                    <span class="mt-0.5 block text-xs text-[var(--text-muted)]">Standard delivery across Malaysia</span>
                                </span>
                                <span v-if="shippingFee !== null" class="text-sm font-semibold text-[var(--brand-primary)]">
                                    {{ formatAmount(shippingFee) }}
                                </span>
                            </label>
                            <div class="mt-3 rounded-sm border border-[var(--border-soft)] bg-[var(--bg-muted)] px-4 py-3 text-sm">
                                <p v-if="isLoadingShipping" class="animate-pulse text-[var(--text-muted)]">Calculating shipping rate…</p>
                                <template v-else-if="shippingFee !== null">
                                    <p v-for="shipment in shippingBreakdown" :key="shipment.label" class="font-medium text-[var(--text-primary)]">{{ shipment.label }}: {{ formatAmount(shipment.fee) }}</p>
                                    <p class="font-medium text-[var(--text-primary)]">Total shipping: {{ formatAmount(shippingFee) }}</p>
                                </template>
                                <p v-else class="text-[var(--text-muted)]">Continue to calculate the exact shipping fee for this address.</p>
                                <p v-if="shippingError" class="mt-1 text-red-600">{{ shippingError }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="state.step === 3">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-9 items-center justify-center rounded-sm bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]">
                                <CreditCard class="size-4.5" aria-hidden="true" />
                            </span>
                            <div>
                                <h2 :class="sectionTitleClass">Payment method</h2>
                                <p :class="sectionHintClass">{{ isPaynetSelected ? 'Placing the order creates a pending order, then starts a PayNet payment.' : 'Placing the order creates a pending order for manual payment. Admin verifies it afterwards.' }}</p>
                            </div>
                        </div>

                        <p v-if="paymentOptions.length === 0" class="mt-5 rounded-sm border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            No payment method is enabled right now. Please contact the store administrator.
                        </p>

                        <div v-else class="mt-5 grid gap-3 sm:grid-cols-2">
                            <label
                                v-for="option in manualOptions"
                                :key="option.value"
                                :class="[
                                    'cursor-pointer rounded-sm border-2 p-4 transition',
                                    paymentMethod === option.value
                                        ? 'border-[var(--brand-primary)] bg-[var(--brand-primary-soft)]'
                                        : 'border-[var(--border-default)] hover:border-[var(--text-faint)]',
                                ]"
                            >
                                <span class="flex items-center gap-2">
                                    <input v-model="paymentMethod" type="radio" :value="option.value" class="size-4 accent-[var(--brand-primary)]" />
                                    <span class="text-sm font-semibold">{{ option.label }}</span>
                                </span>
                                <span class="mt-1.5 block text-xs leading-relaxed text-[var(--text-muted)]">
                                    {{ option.hint }}
                                </span>
                            </label>

                            <fieldset
                                v-if="paynetOptions.length > 0"
                                class="rounded-sm border-2 p-4 transition"
                                :class="isPaynetSelected ? 'border-[var(--brand-primary)] bg-[var(--brand-primary-soft)]' : 'border-[var(--border-default)]'"
                            >
                                <legend class="px-1 text-sm font-semibold">PayNet</legend>
                                <label
                                    v-for="option in paynetOptions"
                                    :key="option.value"
                                    class="flex cursor-pointer items-start gap-2 py-1.5"
                                >
                                    <input v-model="paymentMethod" type="radio" :value="option.value" class="mt-0.5 size-4 accent-[var(--brand-primary)]" />
                                    <span>
                                        <span class="block text-sm font-semibold">{{ option.label }}</span>
                                        <span class="mt-0.5 block text-xs leading-relaxed text-[var(--text-muted)]">{{ option.hint }}</span>
                                    </span>
                                </label>
                            </fieldset>
                        </div>

                        <div v-if="paymentMethod === 'bank_transfer' && bankTransferEnabled" class="mt-4 rounded-sm border border-[var(--border-soft)] bg-[var(--bg-muted)] p-4 text-sm">
                            <p class="font-semibold">Bank transfer details</p>
                            <dl class="mt-2 space-y-1">
                                <div v-if="bankName" class="flex justify-between gap-4">
                                    <dt class="text-[var(--text-muted)]">Bank</dt>
                                    <dd class="font-medium">{{ bankName }}</dd>
                                </div>
                                <div v-if="bankAccountName" class="flex justify-between gap-4">
                                    <dt class="text-[var(--text-muted)]">Account name</dt>
                                    <dd class="font-medium">{{ bankAccountName }}</dd>
                                </div>
                                <div v-if="bankAccountNumber" class="flex justify-between gap-4">
                                    <dt class="text-[var(--text-muted)]">Account number</dt>
                                    <dd class="font-medium">{{ bankAccountNumber }}</dd>
                                </div>
                                <p v-if="!bankName && !bankAccountName && !bankAccountNumber" class="text-[var(--text-muted)]">Bank details have not been set. Please contact the store.</p>
                            </dl>
                        </div>

                        <div v-if="paymentMethod === 'qr_code' && qrCodeEnabled" class="mt-4 rounded-sm border border-[var(--border-soft)] bg-[var(--bg-muted)] p-4 text-sm">
                            <p class="font-semibold">QR code payment</p>
                            <img v-if="qrCodeUrl" :src="qrCodeUrl" alt="Payment QR code" class="mt-2 h-48 w-48 rounded-sm border border-[var(--border-default)] bg-white object-contain" />
                            <p v-else class="mt-2 text-[var(--text-muted)]">QR code image has not been uploaded. Please contact the store.</p>
                        </div>

                        <dl class="mt-5 space-y-2 rounded-sm border border-[var(--border-soft)] bg-[var(--bg-muted)] p-4 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-[var(--text-muted)]">Subtotal</dt>
                                <dd class="font-medium">{{ formatAmount(subtotal) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-[var(--text-muted)]">Shipping</dt>
                                <dd class="font-medium">{{ shippingFee === null ? 'Calculated at previous step' : formatAmount(shippingFee) }}</dd>
                            </div>
                            <div class="flex justify-between border-t border-[var(--border-soft)] pt-2 text-base font-semibold">
                                <dt>Total due</dt>
                                <dd class="text-[var(--brand-primary)]">{{ formatAmount(orderTotal) }}</dd>
                            </div>
                        </dl>

                        <div v-if="submitError" class="mt-4 rounded-sm border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ submitError }}
                        </div>
                    </div>

                    <div v-else-if="state.step === 4 && !createdOrderNumber">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-9 items-center justify-center rounded-sm bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]">
                                <Check class="size-4.5" aria-hidden="true" />
                            </span>
                            <div>
                                <h2 :class="sectionTitleClass">Review your order</h2>
                                <p :class="sectionHintClass">Check the details below, then place your order.</p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            <div class="rounded-sm border border-[var(--border-soft)] p-4 text-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="font-semibold text-[var(--text-primary)]">Buyer</p>
                                    <button type="button" class="text-xs font-medium text-[var(--brand-primary)] hover:underline" @click="setStep(1)">
                                        Edit
                                    </button>
                                </div>
                                <p class="mt-1.5 font-medium">{{ buyer.name || '—' }}</p>
                                <p class="text-[var(--text-secondary)]">{{ buyer.phone || '—' }}<span v-if="buyer.email"> · {{ buyer.email }}</span></p>
                                <p class="mt-1 text-[var(--text-muted)]">{{ buyerFullAddress || '—' }}</p>
                            </div>

                            <div class="rounded-sm border border-[var(--border-soft)] p-4 text-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="font-semibold text-[var(--text-primary)]">Delivery</p>
                                    <button type="button" class="text-xs font-medium text-[var(--brand-primary)] hover:underline" @click="setStep(2)">
                                        Edit
                                    </button>
                                </div>
                                <p class="mt-1.5">{{ shippingFullAddress || '—' }}</p>
                                <p class="mt-1 text-[var(--text-muted)]">
                                    Fixed rate shipping · {{ shippingFee === null ? '—' : formatAmount(shippingFee) }}
                                </p>
                            </div>

                            <div class="rounded-sm border border-[var(--border-soft)] p-4 text-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="font-semibold text-[var(--text-primary)]">Payment</p>
                                    <button type="button" class="text-xs font-medium text-[var(--brand-primary)] hover:underline" @click="setStep(3)">
                                        Edit
                                    </button>
                                </div>
                                <p class="mt-1.5 font-medium">{{ selectedPaymentLabel }}</p>
                                <p class="text-[var(--text-muted)]">{{ selectedPaymentOption?.hint ?? '' }}</p>
                                <p v-if="paymentMethod === 'bank_transfer' && bankAccountNumber" class="mt-1 text-[var(--text-secondary)]">
                                    {{ bankName }} {{ bankAccountNumber }}<span v-if="bankAccountName"> ({{ bankAccountName }})</span>
                                </p>
                            </div>
                        </div>

                        <dl class="mt-4 space-y-2 rounded-sm border border-[var(--border-soft)] bg-[var(--bg-muted)] p-4 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-[var(--text-muted)]">Subtotal</dt>
                                <dd class="font-medium">{{ formatAmount(subtotal) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-[var(--text-muted)]">Shipping</dt>
                                <dd class="font-medium">{{ shippingFee === null ? '—' : formatAmount(shippingFee) }}</dd>
                            </div>
                            <div class="flex justify-between border-t border-[var(--border-soft)] pt-2 text-base font-semibold">
                                <dt>Total due</dt>
                                <dd class="text-[var(--brand-primary)]">{{ formatAmount(orderTotal) }}</dd>
                            </div>
                        </dl>

                        <div v-if="submitError" class="mt-4 rounded-sm border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ submitError }}
                        </div>
                    </div>

                    <div v-else>
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-9 items-center justify-center rounded-sm bg-[var(--accent-cyan)]/10 text-[var(--accent-cyan)]">
                                <Check class="size-4.5" aria-hidden="true" />
                            </span>
                            <div>
                                <h2 :class="sectionTitleClass">Order {{ createdOrderNumber ?? 'created' }}</h2>
                                <p :class="sectionHintClass">Payment status: {{ paymentStatus ?? 'pending' }}.</p>
                            </div>
                        </div>
                        <div class="mt-4 rounded-sm border border-[var(--border-soft)] bg-[var(--bg-muted)] p-4 text-sm">
                            <template v-if="isPaynetSelected">
                                <p v-if="paymentRedirectUrl">
                                    Continue to PayNet:
                                    <a :href="paymentRedirectUrl" class="font-medium text-[var(--brand-primary)] underline">Pay with FPX</a>
                                </p>
                                <p v-if="paymentQrPayload" class="mt-1 break-all">
                                    DuitNow QR reference: <span class="font-medium">{{ paymentQrPayload }}</span>
                                </p>
                                <p v-if="isPolling" class="mt-2 animate-pulse text-xs text-[var(--text-muted)]">Checking payment status…</p>
                                <p v-if="!paymentRedirectUrl && !paymentQrPayload && !submitError" class="text-[var(--text-muted)]">
                                    Your PayNet session link will appear here once created.
                                </p>
                            </template>
                            <template v-else>
                                <p v-if="paymentMethod === 'bank_transfer'">
                                    Transfer <span class="font-semibold">{{ formatAmount(orderTotal) }}</span> to {{ bankName || 'the store bank account' }}<span v-if="bankAccountNumber"> {{ bankAccountNumber }}</span><span v-if="bankAccountName"> ({{ bankAccountName }})</span>, then wait for admin verification.
                                </p>
                                <p v-else-if="paymentMethod === 'qr_code'">
                                    Pay <span class="font-semibold">{{ formatAmount(orderTotal) }}</span> with the QR code above, then wait for admin verification.
                                </p>
                                <p v-else>
                                    Your manual payment is pending admin verification.
                                </p>
                            </template>
                        </div>

                        <div v-if="submitError" class="mt-4 rounded-sm border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ submitError }}
                        </div>

                        <div v-if="createdOrderNumber" class="mt-4 flex flex-col gap-2.5 sm:flex-row">
                            <a
                                :href="`/checkout/confirmation/${createdOrderNumber}?payment_method=${paymentMethod}`"
                                class="flex h-11 flex-1 items-center justify-center rounded-sm bg-[var(--brand-primary)] px-6 text-sm font-semibold text-white transition hover:bg-[var(--brand-primary-hover)] sm:flex-none sm:px-10"
                            >
                                View order confirmation
                            </a>
                            <button
                                v-if="submitError"
                                type="button"
                                :disabled="isSubmitting"
                                class="flex h-11 items-center justify-center rounded-sm border border-[var(--border-default)] bg-white px-6 text-sm font-medium transition hover:bg-[var(--bg-muted)] disabled:opacity-50"
                                @click="placeOrder"
                            >
                                {{ isSubmitting ? 'Retrying…' : 'Retry payment' }}
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col-reverse gap-2.5 sm:flex-row">
                        <button
                            v-if="!createdOrderNumber && state.step > 1"
                            type="button"
                            class="flex h-11 items-center justify-center rounded-sm border border-[var(--border-default)] bg-white px-6 text-sm font-medium text-[var(--text-primary)] transition hover:bg-[var(--bg-muted)]"
                            @click="back"
                        >
                            Back
                        </button>
                        <button
                            v-if="!createdOrderNumber && state.step < 4"
                            type="button"
                            :disabled="isLoadingShipping"
                            class="flex h-11 flex-1 items-center justify-center rounded-sm bg-[var(--brand-primary)] px-6 text-sm font-semibold text-white transition hover:bg-[var(--brand-primary-hover)] disabled:opacity-50 sm:flex-none sm:px-10"
                            @click="next"
                        >
                            {{ isLoadingShipping ? 'Calculating…' : 'Continue' }}
                        </button>
                        <button
                            v-if="state.step === 4 && !createdOrderNumber"
                            type="button"
                            :disabled="isSubmitting || paymentOptions.length === 0"
                            class="flex h-11 flex-1 items-center justify-center rounded-sm bg-[var(--brand-primary)] px-6 text-sm font-semibold text-white transition hover:bg-[var(--brand-primary-hover)] disabled:opacity-50 sm:flex-none sm:px-10"
                            @click="placeOrder"
                        >
                            {{ isSubmitting ? 'Placing order…' : isPaynetSelected ? `Pay ${formatAmount(orderTotal)}` : `Place order · ${formatAmount(orderTotal)}` }}
                        </button>
                    </div>
                </section>

                <aside class="rounded-sm border border-[var(--border-default)] bg-white p-4 md:sticky md:top-24">
                    <h2 class="text-sm font-semibold text-[var(--text-primary)]">Order summary</h2>
                    <template v-if="cartCheckout">
                        <ul class="mt-3 space-y-2 border-b border-[var(--border-soft)] pb-3">
                            <li
                                v-for="item in cartItems"
                                :key="item.productId"
                                class="flex justify-between gap-2 text-sm"
                            >
                                <span class="min-w-0 truncate text-[var(--text-secondary)]">
                                    {{ item.name }}
                                    <span class="text-[var(--text-muted)]">× {{ item.quantity }}</span>
                                </span>
                                <span class="shrink-0 font-medium">{{ formatAmount(item.price * item.quantity) }}</span>
                            </li>
                        </ul>
                        <p class="mt-3 text-xs text-[var(--text-muted)]">
                            {{ itemCount }} item{{ itemCount === 1 ? '' : 's' }} from your cart
                        </p>
                    </template>
                    <template v-else-if="product">
                        <p class="mt-2 truncate text-sm font-medium text-[var(--text-primary)]">{{ product.name }}</p>
                        <p class="mt-1 text-xs text-[var(--text-muted)]">Quantity: {{ quantity }}</p>
                    </template>

                    <dl class="mt-3 space-y-1.5 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-[var(--text-muted)]">Subtotal</dt>
                            <dd class="font-medium">{{ formatAmount(subtotal) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-[var(--text-muted)]">Shipping</dt>
                            <dd class="font-medium">{{ shippingFee === null ? '—' : formatAmount(shippingFee) }}</dd>
                        </div>
                        <div class="flex justify-between border-t border-[var(--border-soft)] pt-2 text-base font-semibold">
                            <dt>Total</dt>
                            <dd class="text-[var(--brand-primary)]">{{ formatAmount(orderTotal) }}</dd>
                        </div>
                    </dl>

                    <p class="mt-3 flex items-start gap-1.5 text-[11px] leading-relaxed text-[var(--text-muted)]">
                        <Lock class="mt-0.5 size-3 shrink-0" aria-hidden="true" />
                        Secure checkout. Stock is reserved when you place the order.
                    </p>
                </aside>
            </div>
        </div>
    </MarketplaceLayout>
</template>
