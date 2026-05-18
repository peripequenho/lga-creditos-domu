<?php
/**
 * Form: alta manual de cliente. Step 1 del flujo alta manual.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
require_once LGA_CRM_DIR . 'templates/_layout.php';

// Cobradores disponibles para asignar
$cobradores = get_users( array( 'role' => 'cobrador', 'orderby' => 'display_name', 'order' => 'ASC' ) );

lga_crm_layout_open( 'Nuevo cliente · Alta manual' );
lga_crm_flash();
?>
<div class="mb-4 text-sm"><a class="lga-link" href="<?php echo esc_url( home_url( '/panel/admin' ) ); ?>">← Volver al panel</a></div>
<h1 class="text-2xl font-bold mb-1">Alta manual de cliente</h1>
<p class="text-sm text-zinc-500 mb-6">Paso 1 de 2 · Datos del cliente. Después podés asignarle un crédito.</p>

<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-6">
    <input type="hidden" name="action" value="lga_create_cliente">
    <?php wp_nonce_field( 'lga_create_cliente' ); ?>

    <!-- Identidad -->
    <fieldset class="bg-white rounded-lg border border-zinc-200 p-5">
        <legend class="text-sm font-semibold px-2">Identidad</legend>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Nombre *</span>
                <input type="text" name="first_name" required class="w-full border border-zinc-300 rounded p-2">
            </label>
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Apellido *</span>
                <input type="text" name="last_name" required class="w-full border border-zinc-300 rounded p-2">
            </label>
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">DNI * (sólo números)</span>
                <input type="text" name="dni" required inputmode="numeric" pattern="[0-9]{7,9}" class="w-full border border-zinc-300 rounded p-2">
            </label>
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Fecha nacimiento</span>
                <input type="date" name="birth_date" class="w-full border border-zinc-300 rounded p-2">
            </label>
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Teléfono * (+549...)</span>
                <input type="tel" name="phone" required placeholder="+5493815551234" class="w-full border border-zinc-300 rounded p-2">
            </label>
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Email</span>
                <input type="email" name="email" class="w-full border border-zinc-300 rounded p-2">
            </label>
        </div>
    </fieldset>

    <!-- Domicilio -->
    <fieldset class="bg-white rounded-lg border border-zinc-200 p-5">
        <legend class="text-sm font-semibold px-2">Domicilio</legend>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="block text-sm md:col-span-2">
                <span class="block mb-1 text-xs text-zinc-600">Dirección</span>
                <input type="text" name="address_line" class="w-full border border-zinc-300 rounded p-2">
            </label>
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Localidad</span>
                <input type="text" name="locality" class="w-full border border-zinc-300 rounded p-2">
            </label>
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Provincia</span>
                <input type="text" name="province" value="Tucumán" class="w-full border border-zinc-300 rounded p-2">
            </label>
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Código postal</span>
                <input type="text" name="postal_code" class="w-full border border-zinc-300 rounded p-2">
            </label>
        </div>
    </fieldset>

    <!-- Ocupación -->
    <fieldset class="bg-white rounded-lg border border-zinc-200 p-5">
        <legend class="text-sm font-semibold px-2">Ocupación e ingresos</legend>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Ocupación</span>
                <select name="occupation" class="w-full border border-zinc-300 rounded p-2">
                    <option value="">— Elegir —</option>
                    <option value="employed_registered">Empleado registrado</option>
                    <option value="self_employed_registered">Monotributista / Autónomo</option>
                    <option value="unregistered">Trabajo informal</option>
                    <option value="retired">Jubilado</option>
                    <option value="homemaker">Ama de casa</option>
                    <option value="student">Estudiante</option>
                    <option value="informal">Changas</option>
                    <option value="other">Otra</option>
                </select>
            </label>
            <label class="block text-sm">
                <span class="block mb-1 text-xs text-zinc-600">Ingreso declarado (ARS / mes)</span>
                <input type="number" name="declared_income_ars" min="0" class="w-full border border-zinc-300 rounded p-2">
            </label>
        </div>
    </fieldset>

    <!-- Asignación -->
    <fieldset class="bg-white rounded-lg border border-zinc-200 p-5">
        <legend class="text-sm font-semibold px-2">Asignación (opcional)</legend>
        <label class="block text-sm">
            <span class="block mb-1 text-xs text-zinc-600">Cobrador asignado</span>
            <select name="cobrador" class="w-full border border-zinc-300 rounded p-2">
                <option value="">— Sin asignar todavía —</option>
                <?php foreach ( $cobradores as $u ): ?>
                    <option value="<?php echo (int) $u->ID; ?>"><?php echo esc_html( $u->display_name ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="block text-sm mt-3">
            <span class="block mb-1 text-xs text-zinc-600">Notas internas</span>
            <textarea name="internal_notes" rows="3" class="w-full border border-zinc-300 rounded p-2" placeholder="Observaciones del alta..."></textarea>
        </label>
    </fieldset>

    <div class="flex justify-end gap-3">
        <a href="<?php echo esc_url( home_url( '/panel/admin' ) ); ?>" class="px-4 py-2 rounded text-sm text-zinc-700 hover:bg-zinc-100">Cancelar</a>
        <button type="submit" class="px-5 py-2 rounded bg-emerald-700 text-white text-sm font-medium hover:bg-emerald-800">
            Guardar cliente y continuar →
        </button>
    </div>
</form>

<?php lga_crm_layout_close();
