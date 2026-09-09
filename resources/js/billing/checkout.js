const checkout = document.querySelector('[data-mollie-checkout]');
const purchaseType = checkout?.querySelector('[data-purchase-type]');
const businessFields = checkout?.querySelector('[data-business-fields]');

const syncBusinessFields = () => {
    if (!purchaseType || !businessFields) {
        return;
    }

    const business = purchaseType.value === 'business';
    businessFields.hidden = !business;
    businessFields.querySelectorAll('input, select').forEach((field) => {
        field.required = business;
        field.disabled = !business;
    });
};

purchaseType?.addEventListener('change', syncBusinessFields);
syncBusinessFields();
