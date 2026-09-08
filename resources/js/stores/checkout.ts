import { reactive, ref } from 'vue';

export type BuyerInformation = {
    name: string;
    address: string;
    state: string;
    city: string;
    post_code: string;
    phone: string;
    email: string;
};

export type CheckoutStep = 1 | 2 | 3 | 4;

type CheckoutState = {
    productId: number | null;
    productSlug: string;
    quantity: number;
    buyer: BuyerInformation;
    shippingMethod: string;
    paymentMethod: string;
    step: CheckoutStep;
};

const state = reactive<CheckoutState>({
    productId: null,
    productSlug: '',
    quantity: 1,
    buyer: {
        name: '',
        address: '',
        state: '',
        city: '',
        post_code: '',
        phone: '',
        email: '',
    },
    shippingMethod: 'fixed',
    paymentMethod: 'fpx',
    step: 1,
});

const orderNumber = ref<string | null>(null);

export function useCheckoutStore() {
    function startBuy(productId: number, slug: string): void {
        state.productId = productId;
        state.productSlug = slug;
        state.quantity = 1;
        state.step = 1;
        orderNumber.value = null;
    }

    function setStep(step: CheckoutStep): void {
        state.step = step;
    }

    function setOrderNumber(value: string | null): void {
        orderNumber.value = value;
    }

    function reset(): void {
        state.productId = null;
        state.productSlug = '';
        state.quantity = 1;
        state.buyer = {
            name: '',
            address: '',
            state: '',
            city: '',
            post_code: '',
            phone: '',
            email: '',
        };
        state.shippingMethod = 'fixed';
        state.paymentMethod = 'fpx';
        state.step = 1;
        orderNumber.value = null;
    }

    return {
        state,
        orderNumber,
        startBuy,
        setStep,
        setOrderNumber,
        reset,
    };
}
