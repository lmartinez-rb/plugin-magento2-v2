define(
    [
        'ko',
        'Decidir_AdminPlanesCuotas/js/view/checkout/summary/costo'
    ],
    function (ko,Component)
    {
        'use strict';

        return Component.extend(
        {
            defaults:
            {
                costoVisible : ko.observable(false),
                title: ko.observable('Costo financiero')
            },
            initialize: function ()
            {
                this._super();
            },
            /**
             * @override
             */
            isDisplayed: function () {
                return false;
            }
        });
    }
);
