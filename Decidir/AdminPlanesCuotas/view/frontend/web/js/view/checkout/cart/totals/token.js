define(
    [
        'ko',
        'Decidir_AdminPlanesCuotas/js/view/checkout/summary/token'
    ],
    function (ko,Component)
    {
        'use strict';

        return Component.extend(
        {
            defaults:
            {
                title: ko.observable('token')
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
