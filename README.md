<a name="inicio"></a>
Módulo Magento 2 para Decidir.
============

Plug in para la integración con gateway de pago <strong>Decidir</strong>
- [Consideraciones Generales](#consideracionesgenerales)
- [Instalación](#instalacion)
- [Configuración](#configuracion)- [Datos adiccionales para prevención de fraude](#cybersource)
- [Funcionalidades Backend](#backend)
- [Backend - visualización de cuotas y descuentos](#backenddesc)
- [Funcionalidades Frontend](#frontend)
- [Frontend - Visualización de descuentos](#frontenddesc)

<a name="consideracionesgenerales"></a>
## Consideraciones generales.
El plug in de pagos de Decidir, provee a las tiendas Magento de un nuevo método de pago, integrando la tienda al gateway de pago. La versión de este plug in esta testeada en PHP 5.4 en adelante y MAGENTO 2.
A continuación se explicarán las funcionalidades en dicho Plugin. Es sumamente importante que se compare con las necesidades de negocio para evaluar la utilización del mismo o recurrir a una integración vía SDK.

<a name="instalacion"></a>
## Instalación del plug in

A. Descomprimir el archivo magento2-plugin-master.zip.

B. Copiar todo su contenido en la carpeta app/code/Decidir

Luego,
+ Ejecutar los siguientes comandos de configuración de Magento desde la consola

```
php bin/magento module:enable Decidir
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy es_AR #idioma instalado de la tienda.
```

+ Refrescar el cache de Magento desde 'System -> Cache Management'
+ Luego ir a 'Stores -> Configuration -> Sales -> Payment Methods' y configurar desde la pestaña de Decidir.

Observación: Descomentar: extension=php_curl.dll y extension=php_openssl.dll del php.ini, ya que para la conexión al gateway se utiliza un API REST, conectándose por medio de PHP.

<a name="configuracion"></a>
## Configuración general
Para configurar el módulo es necesario ingresar a la opción del menú Stores > Configuration > Sales > Payment Methods, en esta página se listan todos los módulos de pago disponibles. Se debe buscar "SPS DECIDIR 2".
(*) Para el correcto funcionamiento del módulo debe estar completo el campo "Public key" y "Private key" (Provistos por Decidir).
Para habilitar o deshabilitar el módulo se debe seleccionar el campo "Habilitado" en la opción correspondiente.
Para editar el nombre que muestra al usuario final, se debe utilizar el campo "Título del método de pago" (Este se muestra en el último paso del checkout).

En el campo "Modo" se puede seleccionar si se quiere trabajar en Sandbox o Producción.
El campo "Estado de órdenes aprobadas" permite elegir el estado en que quedará cada orden en Magento, al ser aprobada por Decidir.

<a name="cybersource"></a>
## Prevención de Fraude.
El plug in soporta las verticales Ticketing y Digitalgoods de Cybersource.

<a name="backend"></a>
## Funcionalidades Backend
General: Provee la administración de la configuración específica del módulo de pago, entorno, credenciales etc.
(*) Ingresando en la opción del menú 'Decidir planes de pago', vas a poder encontrar las siguientes opciones:

**Administrar promociones:**
Se pueden crear promociones o planes de pago en base a las distintas combinaciones de Tarjeta y Banco.

Campos en el formulario para dicha acción:

+ Tarjeta
+ Banco
+ Rango de fechas de vigencia del plan.
+ Prioridad (por si hay más de un plan con la misma tarjeta)
+ Días de la semana aplicables.
+ Interés.

+ Reintegro bancario El campo "Reintegro bancario" se refiere al reintegro que realiza el banco. Puede ser nomial o porcentual. Es un campo a título informativo. En caso que se utilice se visualiza el porcentaje o valor nominal de descuento en el checkout. Por ejemplo: “3 cuotas fijas de $29 – 10% reintegro”, sin calcular el descuento de cada cuota fija.

+ Descuento
El campo "Descuento" es para cuando el comercio realiza un descuento. Puede ser nomial o porcentual. En caso de que se utilice, se visualiza el porcentaje o valor nominal de descuento en el checkout. Por ejemplo: “3 cuotas fijas de $29 – 10% descuento”, sin calcular el descuento de cada cuota fija.

+ Cuota a enviar
Cuando se utilice algún plan que requiera enviar un número de cuotas distinto al correspondiente (por ejemplo Ahora 12) se debe colocar en este campo el valor a enviar a Decidir

OBS: Se pueden listar promociones actuales, editarlas (con algunas acciones masivas desde la grilla) o desactivarlas.

**Prioridad:**
Si hay dos o más planes para la misma “Tarjeta – banco”, se mostrará el de menor prioridad (menor número = mayor prioridad).

**Interés:**
El cálculo del interés se hace con el importe final de la orden de Magento. Por ejemplo: productos + envío.

**Configuración de cuotas:**
Las cuotas se calculan con el interés configurado en cada plan.

Para configurar las cuotas hay que añadir planes como si fuera una promoción más y se puede configurar el interés para cada cuota.

**Administrar Tarjetas:**
Se visualizan las tarjetas y se pueden editar los nombres junto a la acción de activar/desactivar.

(*) No permite añadir nuevas tarjetas – Se deben solicitar a Decidir (Esto implica actualizar el Plugin).

**Administrar Bancos:**
Se visualizan los bancos y se pueden editar los nombres junto a la acción de activar/desactivar.

(*) No permite añadir nuevos bancos– Se deben solicitar a Decidir (Esto implica actualizar el Plugin).

**Devoluciones:**
Se puede realizar una devolución desde las opciones de Magento y con esto hacer también la devolución por Decidir. Para esto ingresar a la orden, al Invoice y luego crear un Credit Memo en ese Invoice.

----------------------------------------------------------------
<a name="backenddesc"></a>
## Backend - visualización de cuotas y descuentos

**Pedidos:**
+ En la grilla de pedidos el importe total es el del pedido sin incluir intereses ni descuentos.
+ Dentro del pedido se visualiza y resta el descuento (al monto de la orden. Sin incluir interés)
+ No se visualiza el costo financiero.

**Factura:**
+ Dentro de la factura no se visualiza el descuento ni reintegro. Se muestra el importe de los productos + envío.
+ Se visualiza cantidad, valor de cuotas y tarjeta-banco utilizado.
+ En la opción para imprimir la factura no se visualiza costo financiero, descuentos, reintegros, ni tarjeta-banco utilizados.

<a name="frontend"></a>
## Funcionalidades Frontend
**Calculadora de cuotas:**
En la página del producto se muestra la calculadora de cuotas de acuerdo a los planes de pago configurados.

**Checkout:**
Al añadir una tarjeta es necesario completar la marca y el banco.
Se pueden seleccionar solamente los planes de cuotas o promociones configuradas en la opción "Administrar promociones".
En caso que el usuario haya pagado previamente con este módulo, permite utilizar esas mismas tarjetas, completando solamente el CCV. (Pago Tokenizado).
Luego de elegir la tarjeta y el banco, muestra el valor de cada cuota.

**Checkout – Visualización de cuotas:**
+ El valor de cada cuota se visualiza en el checkout, en el último paso de compra (al elegir el medio de pago).
+ Las cuotas se visualizan con el valor de cada una. El monto es el de la orden final de Magento. Por ejemplo: productos más envío.

**Historial de pedidos:**
En el listado de compras del usuario final, se visualiza al ingresar a la factura de cada compra, en los comentarios la tarjeta-banco, cantidad de cuotas y valor de cada una. Por ejemplo “Pago realizado con VISA y BBVA Francés en 6 cuotas fijas de $20,13”

Dentro de la factura cuando se utiliza la opción para imprimirla, no se visualiza, costo financiero ni cuotas.
En el listado de compras del usuario final no se visualiza el costo financiero. Es decir que el total del importe es sin sumar el interés.

<a name="frontenddesc"></a>
## Frontend - Visualización de descuentos
**Superposición:**
En caso que se utilice reintegro bancario y descuento en un mismo plan, se visualiza el texto sobre cuánto es el descuento y reintegro en la calculadora de cuotas del checkout. Por ejemplo: “10% descuento – 5% de reintegro”.

**Checkout:**
En el checkout luego de seleccionar la cuota, al total del importe (incluyendo intereses) se resta el descuento. Se visualiza en el resumen.
Se visualiza a título informativo un ítem del reintegro, con el monto nominal correspondiente al pedido.
El reintegro se calcula con el monto total del pedido restando el descuento.

**Historial de pedidos:**
+ En el historial, en el resumen de compras se visualiza el importe total sin calcular descuento. No se visualiza información del reintegro.
+ Dentro de cada pedido, se visualiza la tarjeta-banco, cantidad de cuotas y resta el descuento.
+ Dentro de cada factura, se visualiza la tarjeta-banco, cantidad de cuotas y resta el descuento.
+ En la opción de imprimir factura, no se visualiza ni resta el descuento ni reintegro.
+ El reintegro bancario no se visualiza en ninguna parte del historial de pedidos.
+ En el comentario de la factura de cada pedido se visualiza el valor de cada cuota sin restar ni visualizar el descuento ni reintegro. 