
(function () {
    const registry = window.wc.wcBlocksRegistry;
    const settingsApi = window.wc.wcSettings;
    const element = window.wp.element;

    const settings = settingsApi.getSetting(
        'sie_retro_simulated_data',
        {}
    );

    const title = settings.title || 'Pago simulado ESRETRO';
    const description = settings.description ||
        'Pago ficticio para el prototipo academico. No se realizará ningún cobro real.';

    registry.registerPaymentMethod({
        name: 'sie_retro_simulated',

        label: element.createElement('span', {}, title),

        content: element.createElement('div', {}, description),

        edit: element.createElement('div', {}, description),

        ariaLabel: title,

        canMakePayment: () => true,

        supports: {
            features: settings.supports || ['products']
        }
    });
})();
