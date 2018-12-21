
/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Decidir_AdminPlanesCuotas/js/model/plan',
        'Magento_Checkout/js/model/totals',
        'Decidir_AdminPlanesCuotas/js/action/get-payment-information',
        'mage/translate'
    ],
    function (
        ko,
        Component,
        $,
        quote,
        priceUtils,
        plan,
        totals,
        getPaymentInformationAction,
        $t
    ) {
        'use strict';
        return Component.extend({
            defaults:{
                template: 'Decidir_SpsDecidir/payment/decidir-form'
            },
            initialize: function ()
            {
                this._super();
                this.enabledToken = window.checkoutConfig.payment.enabledToken;
                this.meses = ['01','02','03','04','05','06','07','08','09','10','11','12'];
                this.tarjetasTokenizadas = window.checkoutConfig.payment.decidirToken;
                this.tarjeta_tokenizada = ko.observable('');
            },
            getCode: function()
            {
                return 'decidir_spsdecidir';
            },
            isActive: function()
            {
                return true;
            },
            validate: function()
            {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },
            getMeses: function ()
            {
                return _.map(this.meses, function(value) {
                    return {
                        'value': value,
                        'text' : value
                    }
                });
            },
            getAnios: function ()
            {
                var anios = [];
                var fecha = new Date();
                var anioActual =  fecha.getFullYear();

                for(var i = 0; i <= 10; i++)
                    anios[i] = anioActual + i;

                return _.map(anios, function(value) {
                    return {
                        'value': value,
                        'text' : value
                    }
                });
            },
            getCuotas: function (planId)
            {
                return window.checkoutConfig.payment.cuotasPorPlanDisponibles[planId];
            },
            getTarjetasTokenizadas: function ()
            {
                return _.map(this.tarjetasTokenizadas, function(value) {
                    return {
                        'value': value.decidir_token_id,
                        'text' : $t('Tarjeta ' + value.nombre_tarjeta + ' terminada en '+ value.card_number_last_4_digits)
                    }
                });
            },
            getToken: function (tokenId)
            {
                var token   = new Object();

                $.each(this.tarjetasTokenizadas, function (index,value)
                {
                    if(value.decidir_token_id == tokenId)
                        token = value;
                });

                return token;
            },
            /**
             * Metodo para traer la autorizacion desde decidir
             */
            getAuthorizeRequest: function()
            {
                //console.log('getAuthorizeRequest');
                $('#sps-pagar-btn').prop('disabled', true);
                $( "input[name='payment[method]']" ).prop('disabled', true); //Radiobox de cada medio de pago

                
                var planesDisponibles = plan.getPlanesDisponibles();
                var detallesPlan;
                var tarjeta_id = $("[name='tarjeta']:checked").val();
                var sps_id     = $("[name='tarjeta']:checked").attr('data-id-sps');
                var banco_id   = $("[name='banco']:checked").val();
                var cuotas     = $("[name='plan']:checked").val();
                var plan_pago_id = $('.box-plan-cuota.cuota-seleccionada').attr('id').split('_')[1];
                var textoHtml;
                var trCftHtml;
                var trTeaHtml;
                var trCfHtml;                


                var infoPlanCuota = {plan_pago_id:plan_pago_id, cuota:cuotas};
                $('.adminplanes-loader').removeClass('no-display-2');

                /**
                 * Se elimina cada vez que se instancia, debido a que sps crea nuevamente esta div cada vez
                 * que se instancia el Payment.init()
                 */
                $('#boxSecCodeHelpContainer').remove();

                var planSeleccionado = new Object();

                $.each(planesDisponibles,function(index, val)
                {
                    if(val.tarjeta_id == tarjeta_id && val.banco_id == banco_id && val.plan_pago_id == plan_pago_id)
                    {
                        planSeleccionado.id     = val.plan_pago_id;
                        var detalleCuotasHtml   = $("[name='plan']:checked").parent().children('.right-cuota').children('span.cuota').html();

                        detallesPlan =
                            "<li>AATarjeta: <span class='detalle-plan'>"+val.tarjeta_nombre+"</span></li>"+
                            "<li>Entidad financiera: <span class='detalle-plan'>"+val.banco_nombre+"</span></li>"+
                            "<li>Cuotas: <span class='detalle-plan'>"+detalleCuotasHtml+"</span></li>";
                        return true;
                    }
                });

                $.each(this.getCuotas(planSeleccionado.id),function(index, val)
                {
                    if(val.cuota == cuotas)
                    {
                        planSeleccionado.cuota = val;
                        return true;
                    }
                });

                planSeleccionado.detalleReintegro = this.getDetalleReintegro(planSeleccionado.cuota.reintegro,planSeleccionado.cuota.tipo_reintegro);

                $('.detalles-plan-seleccionado').html(detallesPlan);                            
                            //console.log('planSeleccionado.cuota.descuento = '+planSeleccionado.cuota.descuento);

                          
                            if(planSeleccionado.cuota.interes  > 1)
                            {   
                                 //console.log("call ajax");
                                 $.ajax('/rest/V1/costo/plan_pago/'+planSeleccionado.cuota.plan_pago_id+'/cuota/'+planSeleccionado.cuota.cuota,
                                 {
                                        method  : 'PUT',
                                        context : this,
                                        success : function (response)
                                        {       
                                            $('#sps-pagar-btn').prop('disabled', true); //Botón palce order
                                            $( "input[name='payment[method]']" ).prop('disabled', true); //Radiobox de cada medio de pago

                                                                      
                                            var deferred = $.Deferred();
                                            totals.isLoading(true);
                                            getPaymentInformationAction(deferred);  
                                            $.when(deferred).done(function () {
                                                totals.isLoading(false);

                                                
                                                $('#sps-pagar-btn').prop('disabled', false); //Botón palce order
                                                $( "input[name='payment[method]']" ).prop('disabled', false); //Radiobox de cada medio de pago
                                            });                     

                                            
                                            $('tr.decidir_costo th').text(response);
                                            $('tr.decidir_costo').show();  
                                            if(planSeleccionado.cuota.descuento !=0)
                                            {
                                                //console.log("call ajax");
                                                $.ajax('/rest/V1/descuento/plan_pago/'+planSeleccionado.cuota.plan_pago_id+'/cuota/'+planSeleccionado.cuota.cuota,
                                                {
                                                    method  : 'PUT',
                                                    context : this,
                                                    success : function (response)
                                                    {
                                                        //console.log('Descuento API',response);
                                                        $('#sps-pagar-btn').prop('disabled', true);
                                                        
                                                        var deferred = $.Deferred();
                                                        totals.isLoading(true);


                                                        getPaymentInformationAction(deferred);
                                                        $.when(deferred).done(function () {
                                                            totals.isLoading(false); 
                                                            $('#sps-pagar-btn').prop('disabled', false);  //Botón palce order
                                                            $( "input[name='payment[method]']" ).prop('disabled', false); //Radiobox de cada medio de pago                                  
                                                        });

                                                        
                                                        $('tr.descuento_cuota th').text(response);
                                                        $('tr.descuento_cuota').show();
                                                    },
                                                    error   : function (e, status)
                                                    {
                                                        alert("Se produjo un error, por favor intentelo nuevamente");
                                                        $('.adminplanes-loader').addClass('no-display-2');
                                                        $('tr.descuento_cuota').hide();
                                                    }
                                                });
                                            }
                                        },
                                        error   : function (e, status)
                                        {
                                            alert("Se produjo un error, por favor intentelo nuevamente");
                                            $('.adminplanes-loader').addClass('no-display-2');
                                            $('tr.costo').hide();
                                        }
                                }); 
                            }else{
                                //vuelvo habilitar boton placeholder
                                $('#sps-pagar-btn').prop('disabled', false);
                            }

                            trCftHtml += "<tr class='leyenda-cft'>" +
                                 "<th class='mark'>" +
                                 "<span class=''>CFT</span><span class='value'>" +
                                 "</th>" +
                                 "<td class='amount'>" +
                                 "<span class='price'> "+planSeleccionado.cuota.cft+" %</span>" +
                                 "</td>" +
                                 "</tr>";

                            trTeaHtml += "<tr class='leyenda-tea'>" +
                                "<th class='mark'>" +
                                "<span class=''>TEA</span><span class='value'>" +
                                "</th>" +
                                "<td class='amount'>" +
                                "<span class='price'> "+planSeleccionado.cuota.tea+" %</span>" +
                                "</td>" +
                                "</tr>";
                                

                            if(planSeleccionado.detalleReintegro)
                            {
                                textoHtml = planSeleccionado.detalleReintegro;
                            }

                            textoHtml += trTeaHtml;
                            textoHtml += trCftHtml;
                            textoHtml += trCfHtml;
                            $('table.table-totals tbody').append(textoHtml);


                            $('.selector-cuotas').addClass('no-display-2');
                            $('.tarjetas-disponibles').addClass('no-display-2');
                            $('.bancos-disponibles').addClass('no-display-2');
                            $('.cuotas-disponibles').addClass('no-display-2');
                            $('button.aplicar-plan').addClass('no-display-2');

                            $('.plan-seleccionado').removeClass('no-display-2');

                            $('div.sps-datos-tarjeta').show('slow');
                            $('#sps-pagar-btn').show();                            
                            $('.adminplanes-loader').addClass('no-display-2');
                            $('#boxSecCodeHelpContainer').detach().appendTo('li.sps-codigo-seguridad')

               return false;
            },
            getAuthorizeRequestTokenizado: function ()
            {
                $('#sps-pagar-btn-token').prop('disabled', true); //Botón place order
                $( "input[name='payment[method]']" ).prop('disabled', true); //Radiobox de cada medio de pago


                var planesDisponibles = plan.getPlanesDisponibles();
                var detallesPlan;
                var tarjeta_id   = $("#decidir-token-tarjeta").val();
                var sps_id       = $("#decidir-token-tarjeta").attr('data-id-sps');
                var banco_id     = $("#decidir-token-banco").val();
                var cuotas       = $("[name='plan']:checked").val();
                var plan_pago_id = $('.box-plan-cuota.cuota-seleccionada').attr('id').split('_')[1];
                //console.log('cuotas = ' + cuotas);

                //console.log('tarjeta_id='+tarjeta_id);
                //console.log('sps_id='+sps_id);
                decidir_cuota=cuotas;
                //console.log('decidir_cuota = '+decidir_cuota);





                var infoPlanCuota = {plan_pago_id:plan_pago_id, cuota:cuotas};
                $('.adminplanes-loader').removeClass('no-display-2');

                //
                 // Se elimina cada vez que se instancia, debido a que sps crea nuevamente esta div cada vez
                 // que se instancia el Payment.init()
                 //
                $('#boxSecCodeHelpContainer').remove();

                var planSeleccionado = new Object();

                $.each(planesDisponibles,function(index, val)
                {
                    if(val.tarjeta_id == tarjeta_id && val.banco_id == banco_id && val.plan_pago_id == plan_pago_id)
                    {
                        planSeleccionado.id     = val.plan_pago_id;
                        var detalleCuotasHtml   = $("[name='plan']:checked").parent().children('.right-cuota').children('span.cuota').html();
                        //console.log("arguments",arguments[0].totals);
                        //console.log("cuotas",cuotas);
                        detallesPlan =
                            "<li>Tarjeta: <span class='detalle-plan'>"+val.tarjeta_nombre+"</span></li>"+
                            "<li>Entidad financiera: <span class='detalle-plan'>"+val.banco_nombre+"</span></li>"+
                            "<li>Cuotas: <span class='detalle-plan'>"+detalleCuotasHtml+"</span></li>";  

                        //console.log(detallesPlan);  
                        //console.log("detalleCuotasHtml:",detalleCuotasHtml);                    
                        return true;
                    }
                });

                $.each(this.getCuotas(planSeleccionado.id),function(index, val)
                {
                    if(val.cuota == cuotas)
                    {
                        planSeleccionado.cuota = val;
                        return true;
                    }
                });

                planSeleccionado.detalleReintegro = this.getDetalleReintegro(planSeleccionado.cuota.reintegro,planSeleccionado.cuota.tipo_reintegro);

                $('.cuotas-disponibles').addClass('no-display-2');

                $('.detalles-plan-seleccionado-token').html(detallesPlan).removeClass('no-display-2');
                $('.tarjetas-tokenizadas').show();
                $('.sps-datos-tarjeta-tokenizada').show();

                //console.log('planSeleccionado.cuota.descuento ' + planSeleccionado.cuota.descuento);

                if(planSeleccionado.cuota.costo !=0)
                {
                    //console.log('ini planSeleccionado.cuota.costo');
                    //console.log("call ajax",planSeleccionado);
                    $.ajax('/rest/V1/costo/plan_pago/'+planSeleccionado.cuota.plan_pago_id+'/cuota/'+planSeleccionado.cuota.cuota,
                    {
                        method  : 'PUT',
                        context : this,
                        success : function (response)
                        {
                            $('#sps-pagar-btn-token').prop('disabled', true); //Botón place order
                            $( "input[name='payment[method]']" ).prop('disabled', true); //Radiobox de cada medio de pago
                            //console.log("response",response);
                            var deferred = $.Deferred();
                            totals.isLoading(true);

                            getPaymentInformationAction(deferred);
                            $.when(deferred).done(function () {
                                totals.isLoading(false);
                                $('#sps-pagar-btn-token').prop('disabled', false); //Botón place order
                                $( "input[name='payment[method]']" ).prop('disabled', false); //Radiobox de cada medio de pago
                            });

                            $('tr.decidir_costo th').text(response+" ---");
                            $('tr.decidir_costo').show();





                            if(planSeleccionado.cuota.descuento !=0)
                            {
                                //console.log("call ajax");
                                $.ajax('/rest/V1/descuento/plan_pago/'+planSeleccionado.cuota.plan_pago_id+'/cuota/'+planSeleccionado.cuota.cuota,
                                {
                                    method  : 'PUT',
                                    context : this,
                                    success : function (response)
                                    {
                                        //console.log('Descuento API:',response);
                                        $('#sps-pagar-btn').prop('disabled', true);
                                        
                                        var deferred = $.Deferred();
                                        totals.isLoading(true);


                                        getPaymentInformationAction(deferred);
                                        $.when(deferred).done(function () {
                                            totals.isLoading(false); 
                                            $('#sps-pagar-btn-token').prop('disabled', false); //Botón place order
                                            $( "input[name='payment[method]']" ).prop('disabled', false); //Radiobox de cada medio de pago
                                        });

                                        
                                        $('tr.descuento_cuota th').text(response);
                                        $('tr.descuento_cuota').show();


                                    },
                                    error   : function (e, status)
                                    {
                                        alert("Se produjo un error, por favor intentelo nuevamente");
                                        $('.adminplanes-loader').addClass('no-display-2');
                                        $('tr.descuento_cuota').hide();
                                    }
                                });
                            }







                        },
                        error   : function (e, status)
                        {
                            alert("Se produjo un error, por favor intentelo nuevamente");
                            $('.adminplanes-loader').addClass('no-display-2');
                            $('tr.decidir_costo').hide();
                        }
                    });
                }else{
                    //vuelvo habilitar boton place holder
                    $('#sps-pagar-btn').prop('disabled', false);
                }


                if(planSeleccionado.detalleReintegro)
                {
                    $('table.table-totals tbody').append(planSeleccionado.detalleReintegro);
                }

                $('.adminplanes-loader').addClass('no-display-2');
                $('.sps-codigo-seguridad-token').removeClass('no-display-2');
                $('button.aplicar-cuotas-token').hide();
                $('#sps-pagar-btn-token').show();
                //console.log('Muestra botones de pagar con token');


            },
            cambiarPlan: function ()
            {
                $('[name="tarjeta"]').prop('checked',false);
                $('.box-tarjeta').removeClass('tarjeta-seleccionada');
                $('.leyenda-reintegro').remove();
                $('.tarjetas-disponibles').removeClass('no-display-2');
                $('.selector-cuotas').removeClass('no-display-2');

                $('.plan-seleccionado').addClass('no-display-2');
                $('.sps-datos-tarjeta').hide();

                $('#sps-tarjeta-nombre').val('');
                $('#sps-tarjeta-numero').val('');
                $('#sps-tarjeta-vencimiento').val('');
                $('#sps-tarjeta-codigo-seguridad').val('');
                $('#sps-email').val('');
                $('#sps-tarjeta-codigo-seguridad-helper').val('');

                $('.adminplanes-loader').removeClass('no-display-2');

                //console.log('1 RESET CAMBIAR PLAN en decidir-method.js');
                //console.log("call ajax");
                $.ajax('/rest/V1/descuento/reset',
                {
                    method  : 'GET',
                    context : this,
                    success : function (response)
                    {
                        var deferred = $.Deferred();
                        totals.isLoading(true);

                        getPaymentInformationAction(deferred);
                        $.when(deferred).done(function () {
                            totals.isLoading(false);
                        });
                        $('.adminplanes-loader').addClass('no-display-2');
                        $('tr.descuento_cuota').hide();
                        

                    },
                    error   : function (e, status)
                    {
                        alert("Se produjo un error, por favor intentelo nuevamente");
                        $('.adminplanes-loader').addClass('no-display-2');
                        $('tr.descuento_cuota').hide();
                    }
                });
                

                //console.log('Costo decidir-method RESET 1');
                //console.log("call ajax");
                $.ajax('/rest/V1/costo/reset',
                {
                    method  : 'GET',
                    context : this,
                    success : function (response)
                    {
                        var deferred = $.Deferred();
                        totals.isLoading(true);

                        getPaymentInformationAction(deferred);
                        $.when(deferred).done(function () {
                            totals.isLoading(false);
                        });
                        $('.adminplanes-loader').addClass('no-display-2');
                        
                        $('tr.costo').hide();
                        $('.leyenda-cft').remove();
                        $('.leyenda-tea').remove();
                    },
                    error   : function (e, status)
                    {
                        alert("Se produjo un error, por favor intentelo nuevamente");
                        $('.adminplanes-loader').addClass('no-display-2');
                        $('tr.costo').hide();
                    }
                });                
            },
            getDetalleReintegro: function (reintegro,tipo)
            {
                //console.log('Método detallereintegro ejecutándose');
                var trHtml = '';
                $('.leyenda-reintegro').remove();

                if(reintegro > 0)
                {
                    var valor = 0;

                    /**1 = porcentual**/
                    if(tipo == 1)
                    {
                        var leyenda = reintegro +'%';
                        var totalSinCostoEnvio = (quote.getCalculatedTotal() - quote.shippingMethod().amount);

                        valor = priceUtils.formatPrice((reintegro * totalSinCostoEnvio)/100, quote.getPriceFormat());
                    }
                    else
                    {
                        valor       = priceUtils.formatPrice(reintegro, quote.getPriceFormat());
                        var leyenda = valor;
                    }

                    trHtml += "<tr class='leyenda-reintegro'>" +
                        "<th class='mark'>" +
                        "<span class='destacado'>"+leyenda+" de reintegro</span><span class='value'>" +
                        "(el reintegro se verá reflejado en el resumen bancario, sujeto a condiciones y topes del banco)</span>" +
                        "</th>" +
                        "<td class='amount'>" +
                        "<span class='price'>- "+valor+"</span>" +
                        "</td>" +
                        "</tr>";


                    //console.log('Texto añadido HTML: ' , trHtml);   
                }

                return trHtml;
            },

            aplicarToken: function ()
            {

                

                var tokenId = $('[name="tarjeta-token"]').val();
                var token   = this.getToken(tokenId);

                //console.log('tokenId = ' + tokenId);
                //console.log('token = ' + token);
                //console.log(token);

                if(!jQuery.isEmptyObject(token))
                {
                    $('.adminplanes-loader').removeClass('no-display-2');
                    $('#decidir-token').val(token.token);
                    //console.log('token.token = ' + token.token);

                    $('.cuotas-disponibles').removeClass('no-display-2');

                    $('[name="tarjeta-token"]').hide();
                    $('.decidir-token-descripcion').hide();
                    /**$('ul.tarjetas-tokenizadas').hide();**/

                    $('#selector-planes').show();
                    $('.tarjetas-disponibles').hide();


                    $('.tarjetas-tokenizadas').hide();
                    $('.sps-datos-tarjeta-tokenizada').hide();
                    $('#aplicar-tarjeta').hide();

                    /**var banco       = 'Texto generico de banco';**/
                    var banco_id    = token.banco_id;
                    var tarjeta_id  = token.tarjeta_id; /**jQuery('[name="tarjeta"]:checked').val();*/

                    //console.log('banco_id = ' + banco_id);
                    //console.log('tarjeta_id = ' + tarjeta_id);


                    $('#decidir-token-tarjeta').val(token.tarjeta_id);
                    $('#decidir-token-tarjeta').attr('data-id-sps',token.sps_tarjeta_id);
                    $('#decidir-token-banco').val(token.banco_id);

                    $('button.aplicar-plan').addClass('no-display-2');
                    $('.banco-seleccionado').removeClass('banco-seleccionado');

                    var planesDisponibles = plan.getPlanesDisponibles();
                    var planId = null;
                    //console.log('planesDisponibles');
                    //console.log(planesDisponibles);
                    //console.log("arguments",arguments[0].totals);
                    $.each(planesDisponibles,function(index, val)
                    {
                        //console.log('val.tarjeta_id ' + ' - ' + val.tarjeta_id + ' - ' + tarjeta_id);
                        //console.log('val.banco_id ' + ' - ' + val.banco_id + ' - ' + banco_id);
                        if(val.tarjeta_id == tarjeta_id && val.banco_id == banco_id)
                        {
                            planId = val.plan_pago_id;

                            //console.log('planId = ' + planId);
                        }
                    });
                    //console.log("call ajax");
                    $.ajax(
                    {
                        url     : '/rest/default/V1/carts/mine/payment-information',
                        type    : 'get',
                        context : this,
                        success : function (response)
                        {
                            var subtotal  = response.totals.subtotal;

                            var valorCFT = response.totals.total_segments.find(findcft => findcft.code === 'decidir_costofinanciero');
                            
                            var cuotas = plan.getCuotasDisponibles();
                            //console.log('cuotas');
                            //console.log(cuotas);
                            var argumentsTotals;
                            if (typeof cuotas[planId] != "undefined")
                            {
                                //console.log('planId');
                                //console.log(cuotas[planId]);
                                //console.log("argumentsnt",arguments);
                                //console.log("arguments",arguments[0].totals);
                                argumentsTotals = arguments[0].totals;
                                $('#cuotas-disponibles').empty();
                                $('.cuotas-disponibles').removeClass('no-display-2');

                                $.each(cuotas[planId], function (index,val)
                                {
                                    //console.log(val);

                                    var reintegroHtml = '';
                                    var descuentoHtml = '';
                                    var reintegroBox  = '';
                                    var interesHtml   = '';
                                    var totalCompraDescuento = 0;
                                    //var totalCompra   = subtotal;
                                    var totalCompra = argumentsTotals.base_subtotal + argumentsTotals.shipping_amount;
                                    //console.log("totalCompra:",totalCompra);
                                    if(val.descuento != 0){
                                        totalCompraDescuento = totalCompra * (val.descuento/100);
                                        totalCompra = totalCompra - totalCompraDescuento;
                                    } 
                                    totalCompra = totalCompra * val.interes;
                                    //console.log("totalCompra despues de interes:",totalCompra);
                                    var valorCuota;
                                    //console.log("arguments",argumentsTotals);
                                    //console.log("argumentsnt2",arguments);
                                    //console.log("totalCompra",totalCompra);
                                    if(val.reintegro > 0)
                                    {
                                        reintegroBox = "<div class='reintegro-pop'>"+
                                            "<p>El reintegro es aplicado en el momento de recibir su resumen bancario. Sujeto a condiciones y topes del banco.</p><i class='cerrar-div' onclick='jQuery(\".reintegro-pop\").hide()'></i>"+
                                            "</div>";

                                        if(val.tipo_reintegro == 1)
                                            var reintegro = val.reintegro +'%';
                                        else
                                            var reintegro = priceUtils.formatPrice(val.reintegro, quote.getPriceFormat());

                                        reintegroHtml = "<span class='reintegro'>"+ reintegro +" de reintegro <i class='mas-info' " +
                                            " onclick='jQuery(this).parent().parent().parent().children(\".reintegro-pop\").toggle()' >" +
                                            "</i></span>";
                                    }
                                    //console.log("ESTO ES VAL:",val);
                                    if(val.descuento > 0)
                                    {
                                        if(val.tipo_descuento == 1 && val.descuento < 100)
                                        {
                                            var descuento = val.descuento +'%';
                                            var descuentoNominal = (val.descuento * totalCompra)/100;

                                            valorCuota = priceUtils.formatPrice(((totalCompra - descuentoNominal)/val.cuota), quote.getPriceFormat());
                                            descuentoHtml = "<span class='reintegro descuento'>"+ descuento +" de descuento</span>";
                                        }
                                        else if(val.tipo_descuento == 2 && val.descuento < totalCompra)
                                        {
                                            var descuento = priceUtils.formatPrice(val.descuento, quote.getPriceFormat());

                                            valorCuota = priceUtils.formatPrice(((totalCompra - val.descuento)/val.cuota), quote.getPriceFormat());
                                            descuentoHtml = "<span class='reintegro descuento'>"+ descuento +" de descuento</span>";
                                        }
                                    }
                                    else
                                        valorCuota = priceUtils.formatPrice((totalCompra/val.cuota), quote.getPriceFormat());

                                    if(val.interes == 0)
                                    {
                                        if(val.cuota==1)
                                            interesHtml = val.cuota + ' cuota <strong style="display: inline;">sin inter&eacute;s</strong> de '+'<strong>'+valorCuota+'</strong>';
                                        else
                                            interesHtml = val.cuota + ' cuotas <strong style="display: inline;">sin inter&eacute;s</strong> de '+'<strong>'+valorCuota+'</strong>';
                                    }
                                    else
                                    {
                                    
                                        var valorCuota = priceUtils.formatPrice((totalCompra/val.cuota), quote.getPriceFormat());
                                        
                                        
                                        if(val.cuota==1)
                                            interesHtml = val.cuota + ' cuota fija de '+'<strong>'+valorCuota+'</strong>';
                                        else
                                            interesHtml = val.cuota + ' cuotas fijas de '+'<strong>'+valorCuota+'</strong>';
                                    }

                                    var teaCtfHtml = '';
                                    var teaCtfHtml = '<span class="reintegro descuento">TEA: '+ val.tea +' % - CFT: '+ val.cft +' %</span>';

                                    var onClick = "onclick = \"jQuery(this).children(\'input\').prop(\'checked\',true);" +
                                        "jQuery(\'.box-plan-cuota\').removeClass(\'cuota-seleccionada\');" +
                                        "jQuery('.aplicar-cuotas-token').show();jQuery(this).addClass(\'cuota-seleccionada\')\"";

                                    var boxPlanCuota = "<div class='box-plan-cuota' id='plan_"+planId+"' "+onClick+" >"+
                                        "<input name='plan' value='"+val.cuota+"' type='radio'>"+
                                        "<div class='right-cuota'>"+
                                        "<span class='cuota'>"+interesHtml+"</span>"+
                                        reintegroHtml+teaCtfHtml+descuentoHtml+"</div>"+ reintegroBox+
                                        "</div>";

                                    $('#cuotas-disponibles').append(boxPlanCuota);
                                });
                                $('.adminplanes-loader').addClass('no-display-2');
                            }
                            else
                            {
                                $('.adminplanes-loader').addClass('no-display-2');
                                /**
                                 * ERROR INTERNO: La combinacion de banco y tarjeta no tiene planes disponibles.'
                                 */
                                alert('La tarjeta de crédito seleccionada no tiene planes de pago disponibles. Por favor seleccione otro método de pago.')
                            }
                        },
                        error   : function (e, status)
                        {
                            $('.adminplanes-loader').addClass('no-display-2');
                            alert("Se produjo un error, por favor intentelo nuevamente");
                        }
                    });
                }

                return false;
            },
            cambiarTarjeta: function ()
            {
                //console.log('Descuento decidir-method RESET CAMBIARTARJETA');
                //console.log("call ajax");
                //console.log("thisS:",this);
                $.ajax('/rest/V1/descuento/reset',
                {
                    method  : 'GET',
                    context : this,
                    success : function (response)
                    {
                        var deferred = $.Deferred();
                        totals.isLoading(true);

                        getPaymentInformationAction(deferred);
                        $.when(deferred).done(function () {
                            totals.isLoading(false);
                        });
                        $('.adminplanes-loader').addClass('no-display-2');
                        $('tr.descuento_cuota').hide();

                    },
                    error   : function (e, status)
                    {
                        alert("Se produjo un error, por favor intentelo nuevamente");
                        $('.adminplanes-loader').addClass('no-display-2');
                        $('tr.descuento_cuota').hide();
                    }
                });


                //console.log('COSTO Decidir-method 2');
                //console.log("call ajax");
                $.ajax('/rest/V1/costo/reset',
                {
                    method  : 'GET',
                    context : this,
                    success : function (response)
                    {
                        var deferred = $.Deferred();
                        totals.isLoading(true);

                        getPaymentInformationAction(deferred);
                        $.when(deferred).done(function () {
                            totals.isLoading(false);
                        });
                        $('.adminplanes-loader').addClass('no-display-2');
                        $('tr.costo').hide();
                        
                        $('.leyenda-cft').remove();

                                                
                    },
                    error   : function (e, status)
                    {
                        alert("Se produjo un error, por favor intentelo nuevamente");
                        $('.adminplanes-loader').addClass('no-display-2');
                        $('tr.costo').hide();
                    }
                });
                
                $('tr.leyenda-reintegro').remove();
                $('.leyenda-reintegro').remove();
                $('.tarjeta-almacenada').hide();
                $('#selector-planes').show();
                $('.tarjetas-disponibles').show();
                $('.cuotas-disponibles').addClass('no-display-2');
            },

            /**
             * Si no existe un token generado para el usuario, muestra el formulario de tarjetas y bancos, caso
             * contrario muestra el formulario de tokenizacion.
             */
            noExisteToken: function ()
            {
                var enabledTokenFlag = this.enabledToken;
                //console.log("NO EXISTE TOKEN!!!!!!!!!!!!!");
                //console.log(this.tarjetasTokenizadas.length);
                //console.log(enabledTokenFlag);
                //console.log("arguments",arguments);
                $(".amount strong span.price").html("$0,00");
                if(enabledTokenFlag == "1"){
                    if(this.tarjetasTokenizadas.length)
                        return false;
                    else
                        return true;
                }else{
                    //console.log("else.....");
                    return true;
                }


                
            },

            obtenerToken: function () {
                $("#error-fields").html("");
                var bvalidate = false;
                $('#' + this.getCode() + '-form :input').each(function () {
                    var input = $(this);
                    if($('#'+input.attr('id')).valid() == 0){
                        $("#error-fields").html("Todos los campos son requeridos");
                        bvalidate = true;
                        return false;
                    }  
                });
                if(bvalidate)
                    return false;
                //OBTENER TOKEN DE PAGO Y HACER PAGO                
                //console.log('FUnción obtenerToken');
                require(['jquery', 'jquery/ui'], function($){ 
                    $('#sps-pagar-btn').prop('disabled', true); //Botón place order
                    $( "input[name='payment[method]']" ).prop('disabled', true); //Radiobox de cada medio de pago
                    
                    var form=window.document.querySelector('#decidir_spsdecidir-form');

                    //console.log('Placeorder: '+form);
                    decidirSandbox.createToken(form, sdkResponseHandler);//formulario y callback 
                });
            },


            pagarTarjetaTokenizada: function(){
                $('#sps-pagar-btn-token').prop('disabled', true); //Botón place order
                $( "input[name='payment[method]']" ).prop('disabled', true); //Radiobox de cada medio de pago


                //console.log('decidir_cuota - ANTES - : '+decidir_cuota);
                //Guarda en sesión el token a utilizar
                //decidir_cuota=$("[name='plan']:checked").val();
                //console.log('decidir_cuota = '+decidir_cuota);


                require(['jquery', 'jquery/ui'], function($){ 
                    var form=window.document.querySelector('#decidir_spsdecidir-form-token');

                    //console.log('Placeorder: '+form);
                    decidirSandbox.createToken(form, sdkResponseHandlerTokenizada);//formulario y callback 
                });
            },


            cambiarTarjetaTokenizada: function ()
            { 
                var enabledTokenFlag = this.enabledToken;
                //console.log("enabled token ==>: "+enabledTokenFlag+"\n");
                var tokenId = this.tarjeta_tokenizada();
                var datosToken = this.getToken(tokenId);
                //console.log('datosToken --> '+datosToken.token);
                window.decidir_tarjeta_sps=datosToken.token;
                //console.log('cuota = '+$("[name='plan']:checked").val());

                window.decidir_holderName=datosToken.card_holder_name;
                window.decidir_lastDigits=datosToken.card_number_last_4_digits;
                window.decidir_expirationMonth=datosToken.card_expiration_month;
                window.decidir_expirationYear=datosToken.card_expiration_year;
                window.decidir_tarjeta_sps=datosToken.card_type;
                window.decidir_bin=datosToken.card_bin;

                $('#tarjeta-token_'+tokenId).show();
                $('#decidir-token').val(datosToken.token);

            }
        });
        
    }
);
