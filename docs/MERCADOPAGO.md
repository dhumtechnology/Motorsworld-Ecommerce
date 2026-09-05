# Mercado Pago — legado (el checkout usa Culqi)

Este documento quedó de la integración anterior. El checkout actual cobra con **Culqi** (tarjeta débito/crédito y Yape).

---

Checkout anterior: **tarjeta (crédito/débito) + Yape** vía Payment Brick (formulario embebido, PCI-compliant).

## 0. Qué ya está en el código

- Cliente HTTP: `app/Services/Payments/MercadoPago/MercadoPagoClient.php`
- Cobro: `app/Actions/Payments/ProcessMercadoPagoPaymentAction.php`
- Webhook: `POST/GET /webhooks/mercadopago`
- Checkout UI: `resources/views/shop/checkout/index.blade.php`
- Modo fake: `MERCADOPAGO_FAKE=true` (sin llaves)

Migración BD (una vez):

```bash
php artisan migrate
```

## 1. Probar YA sin cuenta (fake)

En `.env`:

```env
MERCADOPAGO_FAKE=true
MERCADOPAGO_PUBLIC_KEY=
MERCADOPAGO_ACCESS_TOKEN=
```

1. Abre la tienda en local.
2. Agrega productos → Checkout.
3. Completa datos y paga (tarjeta o Yape fake).
4. Debe crear el pedido y marcarlo pagado.

No necesitas CulqiPanel ni cuenta de Mercado Pago.

## 2. Crear cuenta de desarrollador (tú)

1. Entra a [Mercado Pago Developers (PE)](https://www.mercadopago.com.pe/developers).
2. Crea / inicia sesión (puede ser tu usuario; luego se cambia a la empresa).
3. Ve a **Tus integraciones** → **Crear aplicación**.
4. Nombre: `Motoworld Ecommerce`.
5. Producto: **Checkout API** / Payments (Bricks).
6. En **Credenciales de prueba** copia:
   - **Public Key** (`TEST-...`)
   - **Access Token** (`TEST-...`)

## 3. Probar con API real de test

En `.env`:

```env
MERCADOPAGO_FAKE=false
MERCADOPAGO_PUBLIC_KEY=TEST-xxxxxxxx
MERCADOPAGO_ACCESS_TOKEN=TEST-xxxxxxxx
APP_URL=http://localhost  # o tu túnel https
```

Luego:

```bash
php artisan config:clear
```

Tarjetas de prueba (PE): ver docs oficiales  
[Testear pagos](https://www.mercadopago.com.pe/developers/es/docs/checkout-bricks/integration-test/test-payment-flow).

Ejemplo típico Mastercard de prueba: `5031 7557 3456 0604`, CVV `123`, fecha futura, DNI de prueba según la doc.

Yape de prueba: sigue la guía de test de Yape en Developers (OTP/celular de prueba).

## 4. Webhooks (test / prod)

En la app de Mercado Pago → **Webhooks**:

- URL: `https://TU-DOMINIO/webhooks/mercadopago`
- Eventos: **Payments** (`payment`)

En local usa un túnel (ngrok / Cloudflare Tunnel) apuntando a tu app.

## 5. Producción (empresa)

1. La empresa verifica la cuenta Mercado Pago con **RUC Motoworld**.
2. Te pasan **Public Key** y **Access Token** de **producción**.
3. En el servidor:

```env
MERCADOPAGO_FAKE=false
MERCADOPAGO_PUBLIC_KEY=APP_USR-...   # o pk de producción
MERCADOPAGO_ACCESS_TOKEN=APP_USR-...
APP_URL=https://motoworld.pe
```

4. Webhook de producción a `https://motoworld.pe/webhooks/mercadopago`.
5. Un pago real pequeño de validación.

## 6. Métodos en el checkout

El checkout tiene **2 pestañas** (no 2 formularios de tarjeta):

1. **Tarjeta** — un solo formulario (Card Payment Brick) para crédito **y** débito.
2. **Yape** — celular + OTP con `mp.yape().create()` (API oficial PE).

No se ofrece cuenta Mercado Pago ni PagoEfectivo.

## 7. Checklist rápido

| Paso | Quién | Estado |
|------|--------|--------|
| Fake local | Dev | Sin llaves |
| App + llaves TEST | Dev | Developers |
| Webhook test | Dev | Túnel + panel |
| Cuenta empresa verificada | Empresa | RUC |
| Llaves producción | Empresa → Dev | `.env` prod |
| 1er pago real | Ambos | Validar |

## Notas

- Culqi queda en el código como legado; el checkout ya no lo usa.
- No subas Access Tokens al repo.
- Montos MP van en soles (`transaction_amount`), no en céntimos.
