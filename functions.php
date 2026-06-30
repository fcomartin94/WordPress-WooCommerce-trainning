<?php
// Encolar el CSS del tema padre. Sin esto, el child theme
// se ve sin estilos. add_action es el hook que ejecuta tu
// función en el momento de cargar los scripts del front.
add_action('wp_enqueue_scripts', 'cursos_enqueue_parent_style');

function cursos_enqueue_parent_style()
{
    wp_enqueue_style(
        'storefront-parent',
        get_template_directory_uri() . '/style.css'
    );
}

add_action('woocommerce_after_single_product_summary', 'cursos_aviso_matricula', 15);

function cursos_aviso_matricula()
{
    echo '<p class="curso-aviso">Recibirás el acceso al curso por email tras la compra.</p>';
}

add_action('woocommerce_single_product_summary', 'cursos_mostrar_detalles', 25);

function cursos_mostrar_detalles()
{
    global $product;
    $id = $product->get_id();

    $duracion = get_field('duracion', $id);
    $nivel    = get_field('nivel', $id);
    $profe    = get_field('instructor', $id);
    $inicio   = get_field('fecha_inicio', $id);

    // Si el producto no es un curso (sin campos), no imprimas nada
    if (!$duracion && !$nivel) {
        return;
    }

    echo '<ul class="curso-detalles">';
    if ($duracion) echo '<li><strong>Duración:</strong> ' . esc_html($duracion) . ' h</li>';
    if ($nivel)    echo '<li><strong>Nivel:</strong> ' . esc_html($nivel) . '</li>';
    if ($profe)    echo '<li><strong>Instructor:</strong> ' . esc_html($profe) . '</li>';
    if ($inicio)   echo '<li><strong>Inicio:</strong> ' . esc_html($inicio) . '</li>';
    echo '</ul>';
}

add_filter('woocommerce_product_tabs', 'cursos_tab_temario');

function cursos_tab_temario($tabs)
{
    $tabs['temario'] = [
        'title'    => __('Temario', 'storefront-child'),
        'priority' => 15,
        'callback' => 'cursos_tab_temario_contenido',
    ];
    return $tabs;
}

function cursos_tab_temario_contenido()
{
    global $product;
    $temario = get_field('temario', $product->get_id());
    echo $temario ? wp_kses_post($temario) : '<p>Temario disponible próximamente.</p>';
}

add_action('woocommerce_before_calculate_totals', 'cursos_descuento_por_cantidad', 20);

function cursos_descuento_por_cantidad($cart)
{
    if (is_admin() && !wp_doing_ajax()) {
        return;
    }
    if (did_action('woocommerce_before_calculate_totals') >= 2) {
        return;
    }

    $unidades = $cart->get_cart_contents_count();

    $descuento = 0;
    if ($unidades >= 5) {
        $descuento = 0.20;
    } elseif ($unidades >= 3) {
        $descuento = 0.10;
    }

    if ($descuento === 0) {
        return;
    }

    foreach ($cart->get_cart() as $item) {
        $precio_base = $item['data']->get_regular_price();
        $precio_nuevo = $precio_base * (1 - $descuento);
        $item['data']->set_price($precio_nuevo);
    }
}

add_action('woocommerce_before_cart', 'cursos_aviso_descuento');

function cursos_aviso_descuento()
{
    $unidades = WC()->cart->get_cart_contents_count();

    if ($unidades < 3) {
        $faltan = 3 - $unidades;
        wc_print_notice(
            sprintf('Añade %d curso(s) más y consigue un 10%% de descuento.', $faltan),
            'notice'
        );
    } elseif ($unidades < 5) {
        $faltan = 5 - $unidades;
        wc_print_notice(
            sprintf('Tienes un 10%% aplicado. Añade %d más para subir al 20%%.', $faltan),
            'success'
        );
    }
}

add_action('rest_api_init', 'cursos_registrar_endpoint');

function cursos_registrar_endpoint()
{
    register_rest_route('cursos/v1', '/mis-pedidos', [
        'methods'             => 'GET',
        'callback'            => 'cursos_get_mis_pedidos',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ]);
}

function cursos_get_mis_pedidos()
{
    $pedidos = wc_get_orders([
        'customer_id' => get_current_user_id(),
        'limit'       => 20,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ]);

    $salida = [];
    foreach ($pedidos as $pedido) {
        $salida[] = [
            'id'     => $pedido->get_id(),
            'fecha'  => $pedido->get_date_created()->date('d/m/Y'),
            'estado' => wc_get_order_status_name($pedido->get_status()),
            'total'  => $pedido->get_formatted_order_total(),
        ];
    }

    return rest_ensure_response($salida);
}

add_filter('woocommerce_account_menu_items', 'cursos_menu_mis_cursos');
function cursos_menu_mis_cursos($items)
{
    $logout = $items['customer-logout'];
    unset($items['customer-logout']);
    $items['mis-cursos'] = __('Mis cursos', 'storefront-child');
    $items['customer-logout'] = $logout;
    return $items;
}

add_action('init', function () {
    add_rewrite_endpoint('mis-cursos', EP_ROOT | EP_PAGES);
});

add_action('woocommerce_account_mis-cursos_endpoint', 'cursos_contenido_mis_cursos');
function cursos_contenido_mis_cursos()
{
    echo '<h3>Mis cursos</h3>';
    echo '<div id="mis-cursos-app">Cargando tus pedidos...</div>';

    wp_enqueue_script(
        'cursos-mis-cursos',
        get_stylesheet_directory_uri() . '/js/mis-cursos.js',
        [], '1.0', true
    );

    wp_localize_script('cursos-mis-cursos', 'CURSOS_API', [
        'url'   => esc_url_raw(rest_url('cursos/v1/mis-pedidos')),
        'nonce' => wp_create_nonce('wp_rest'),
    ]);
}
