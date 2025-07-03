<?php
// Archivo de administración para BkSyncGreen
// Autor: Greenborn

// Agregar menú y submenú en el admin
add_action('admin_menu', 'bksyncgreen_admin_menu');
function bksyncgreen_admin_menu() {
    add_menu_page(
        'BkSyncGreen', // Título de la página
        'BkSyncGreen', // Título del menú
        'manage_options', // Capacidad
        'bksyncgreen', // Slug
        'bksyncgreen_versiones_page', // Función de contenido
        'dashicons-backup', // Icono
        80 // Posición
    );
    add_submenu_page(
        'bksyncgreen',
        'Versiones',
        'Versiones',
        'manage_options',
        'bksyncgreen',
        'bksyncgreen_versiones_page'
    );
    add_submenu_page(
        'bksyncgreen',
        'Configuración',
        'Configuración',
        'manage_options',
        'bksyncgreen-config',
        'bksyncgreen_config_page'
    );
}

// Registrar y cargar Bootstrap solo en las páginas del plugin
add_action('admin_enqueue_scripts', 'bksyncgreen_cargar_bootstrap_admin');
function bksyncgreen_cargar_bootstrap_admin($hook) {
    // Solo cargar en las páginas del plugin
    if (isset($_GET['page']) && ($_GET['page'] === 'bksyncgreen' || $_GET['page'] === 'bksyncgreen-config')) {
        // Bootstrap 5 CDN
        wp_enqueue_style('bksyncgreen-bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css');
        wp_enqueue_script('bksyncgreen-bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', array('jquery'), null, true);
    }
}

// Registrar y cargar DataTables además de Bootstrap
add_action('admin_enqueue_scripts', 'bksyncgreen_cargar_datatables_admin');
function bksyncgreen_cargar_datatables_admin($hook) {
    if (isset($_GET['page']) && $_GET['page'] === 'bksyncgreen') {
        // Bootstrap ya se carga en el otro hook
        wp_enqueue_style('bksyncgreen-datatables-css', 'https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css');
        wp_enqueue_script('bksyncgreen-datatables-js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array('jquery'), null, true);
        wp_enqueue_script('bksyncgreen-datatables-bs-js', 'https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js', array('bksyncgreen-datatables-js'), null, true);
    }
}

// Función para obtener texto traducido según el idioma configurado
function bksyncgreen_get_text($key) {
    $idioma = get_option('bksyncgreen_idioma', 'es');
    
    // Ruta al archivo de traducción
    $archivo_traduccion = plugin_dir_path(__FILE__) . '../languages/' . $idioma . '.json';
    
    // Si no existe el archivo para el idioma seleccionado, usar español por defecto
    if (!file_exists($archivo_traduccion)) {
        $idioma = 'es';
        $archivo_traduccion = plugin_dir_path(__FILE__) . '../languages/' . $idioma . '.json';
    }
    
    // Cargar las traducciones desde el archivo JSON
    if (file_exists($archivo_traduccion)) {
        $contenido = file_get_contents($archivo_traduccion);
        $traducciones = json_decode($contenido, true);
        
        if (isset($traducciones[$key])) {
            return $traducciones[$key];
        }
    }
    
    // Si no se encuentra la traducción, devolver la clave
    return $key;
}

// Mostrar la tabla de versiones con DataTables y modal para detalles
function bksyncgreen_versiones_page() {
    global $wpdb;
    $tabla_versionado = $wpdb->prefix . 'bksyncgreen_versions';
    $registros_por_pagina = get_option('bksyncgreen_registros_por_pagina', 100);
    $resultados = $wpdb->get_results("SELECT * FROM $tabla_versionado ORDER BY fecha DESC LIMIT $registros_por_pagina");
    echo '<div class="wrap">';
    echo '<h1>' . bksyncgreen_get_text('versiones_registradas') . '</h1>';
    echo '<button id="bksyncgreen-export-json" class="btn btn-success mb-3">' . bksyncgreen_get_text('exportar_json') . '</button>';
    echo '<table id="bksyncgreen-table" class="table table-striped table-bordered table-hover table-sm">';
    echo '<thead><tr><th>' . bksyncgreen_get_text('version') . '</th><th>' . bksyncgreen_get_text('fecha_hora') . '</th><th>' . bksyncgreen_get_text('usuario') . '</th><th>' . bksyncgreen_get_text('detalle') . '</th></tr></thead>';
    echo '<tbody>';
    $version = count($resultados);
    foreach ($resultados as $fila) {
        $usuario = $fila->usuario_id ? get_userdata($fila->usuario_id) : null;
        $nombre_usuario = $usuario ? esc_html($usuario->user_login) : '-';
        $detalle_id = 'detalle_' . esc_attr($fila->id);
        echo '<tr>';
        echo '<td>' . $version-- . '</td>';
        echo '<td>' . esc_html($fila->fecha) . '</td>';
        echo '<td>' . $nombre_usuario . '</td>';
        echo '<td><button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#' . $detalle_id . '">' . bksyncgreen_get_text('ver_detalle') . '</button>';
        // Modal Bootstrap para detalle
        echo '<div class="modal fade" id="' . $detalle_id . '" tabindex="-1" aria-labelledby="' . $detalle_id . 'Label" aria-hidden="true">';
        echo '  <div class="modal-dialog modal-lg">';
        echo '    <div class="modal-content">';
        echo '      <div class="modal-header">';
        echo '        <h5 class="modal-title" id="' . $detalle_id . 'Label">' . bksyncgreen_get_text('detalle_version') . '</h5>';
        echo '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="' . bksyncgreen_get_text('cerrar') . '"></button>';
        echo '      </div>';
        echo '      <div class="modal-body">';
        echo '        <strong>' . bksyncgreen_get_text('tabla') . ':</strong> ' . esc_html($fila->tabla) . '<br>';
        echo '        <strong>' . bksyncgreen_get_text('operacion') . ':</strong> ' . esc_html($fila->operacion) . '<br>';
        echo '        <strong>' . bksyncgreen_get_text('datos_anteriores') . ':</strong><br><textarea readonly style="width:100%;height:80px">' . esc_textarea($fila->datos_anteriores) . '</textarea><br>';
        echo '        <strong>' . bksyncgreen_get_text('datos_nuevos') . ':</strong><br><textarea readonly style="width:100%;height:80px">' . esc_textarea($fila->datos_nuevos) . '</textarea>';
        echo '      </div>';
        echo '      <div class="modal-footer">';
        echo '        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . bksyncgreen_get_text('cerrar') . '</button>';
        echo '      </div>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
    // Script para inicializar DataTables y exportar JSON
    echo '<script>
    jQuery(document).ready(function($){
        var table = $("#bksyncgreen-table").DataTable({"order": [[0, "desc"]]});
        $("#bksyncgreen-export-json").on("click", function(){
            var data = table.rows({search: "applied"}).data().toArray();
            // Convertir a objeto con nombres de columnas
            var headers = [];
            $("#bksyncgreen-table thead th").each(function(){headers.push($(this).text());});
            var jsonData = data.map(function(row){
                var obj = {};
                for(var i=0; i<headers.length; i++){
                    // Eliminar HTML
                    obj[headers[i]] = row[i].replace(/<[^>]+>/g, "");
                }
                return obj;
            });
            var blob = new Blob([JSON.stringify(jsonData, null, 2)], {type: "application/json"});
            var url = URL.createObjectURL(blob);
            var a = document.createElement("a");
            a.href = url;
            a.download = "bksyncgreen_versiones.json";
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    });
    </script>';
}

// Página de configuración
function bksyncgreen_config_page() {
    // Guardar configuración si se envió el formulario
    if (isset($_POST['bksyncgreen_save_config'])) {
        if (wp_verify_nonce($_POST['bksyncgreen_nonce'], 'bksyncgreen_config')) {
            $registros_por_pagina = intval($_POST['registros_por_pagina']);
            $idioma = sanitize_text_field($_POST['idioma']);
            
            if ($registros_por_pagina > 0 && $registros_por_pagina <= 1000) {
                update_option('bksyncgreen_registros_por_pagina', $registros_por_pagina);
                update_option('bksyncgreen_idioma', $idioma);
                echo '<div class="notice notice-success"><p>' . bksyncgreen_get_text('config_guardada') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . bksyncgreen_get_text('error_registros') . '</p></div>';
            }
        }
    }
    
    $registros_actuales = get_option('bksyncgreen_registros_por_pagina', 100);
    $idioma_actual = get_option('bksyncgreen_idioma', 'es');
    
    $idiomas_disponibles = array(
        'es' => 'Español',
        'en' => 'English',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'ru' => 'Русский',
        'zh' => '中文',
        'ja' => '日本語',
        'ko' => '한국어'
    );
    
    echo '<div class="wrap">';
    echo '<h1>' . bksyncgreen_get_text('configuracion') . '</h1>';
    echo '<form method="post" action="">';
    wp_nonce_field('bksyncgreen_config', 'bksyncgreen_nonce');
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row"><label for="registros_por_pagina">' . bksyncgreen_get_text('registros_pagina') . '</label></th>';
    echo '<td><input type="number" id="registros_por_pagina" name="registros_por_pagina" value="' . esc_attr($registros_actuales) . '" min="1" max="1000" class="regular-text" />';
    echo '<p class="description">' . bksyncgreen_get_text('registros_pagina_desc') . '</p></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th scope="row"><label for="idioma">' . bksyncgreen_get_text('idioma') . '</label></th>';
    echo '<td><select id="idioma" name="idioma" class="regular-text">';
    foreach ($idiomas_disponibles as $codigo => $nombre) {
        $selected = ($codigo === $idioma_actual) ? 'selected' : '';
        echo '<option value="' . esc_attr($codigo) . '" ' . $selected . '>' . esc_html($nombre) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">' . bksyncgreen_get_text('idioma_desc') . '</p></td>';
    echo '</tr>';
    echo '</table>';
    echo '<p class="submit">';
    echo '<input type="submit" name="bksyncgreen_save_config" class="button-primary" value="' . bksyncgreen_get_text('guardar_config') . '" />';
    echo '</p>';
    echo '</form>';
    echo '</div>';
} 