export function formatPrice(
    amount: number,
    symbol = 'RM',
    decimals = 2,
): string {
    const formatted = amount.toLocaleString('en-MY', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });

    return `${symbol} ${formatted}`;
}
