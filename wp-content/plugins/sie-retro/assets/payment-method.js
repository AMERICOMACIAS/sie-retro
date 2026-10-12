
( function () {
    'use strict';

    if (
        ! window.wc ||
        ! window.wc.wcBlocksRegistry ||
        ! window.wc.wcSettings ||
        ! window.wp ||
        ! window.wp.element
    ) {
        return;
    }

    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;

    const settings = window.wc.wcSettings.getSetting(
        'sie_retro_simulated_data',
        {}
    );

    const { createElement } = window.wp.element;

    const title = settings.title || 'Tarjeta simulada';

    const description = settings.description ||
        'Pago simulado para el prototipo académico. No se realiza ningún cargo.';

    const Content = function () {
        return createElement(
            'div',
            {
                className: 'sie-retro-simulated-payment-description'
            },
            description
        );
    };

    const Label = function ( props ) {
        return createElement(
            'span',
            {
                className: props && props.className
                    ? props.className
                    : ''
            },
            title
        );
    };

    registerPaymentMethod( {
        name: 'sie_retro_simulated',

        label: createElement( Label, null ),

        content: createElement( Content, null ),

        edit: createElement( Content, null ),

        canMakePayment: function () {
            return true;
        },

        ariaLabel: title,

        supports: {
            features: settings.supports || [ 'products' ],
        },
    } );
} )();