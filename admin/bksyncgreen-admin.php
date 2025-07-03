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

// Mostrar la tabla de versiones con DataTables y modal para detalles
function bksyncgreen_versiones_page() {
    global $wpdb;
    $tabla_versionado = $wpdb->prefix . 'bksyncgreen_versions';
    $resultados = $wpdb->get_results("SELECT * FROM $tabla_versionado ORDER BY fecha DESC LIMIT 100");
    echo '<div class="wrap">';
    echo '<h1>Versiones registradas</h1>';
    echo '<button id="bksyncgreen-export-json" class="btn btn-success mb-3">Exportar JSON</button>';
    echo '<table id="bksyncgreen-table" class="table table-striped table-bordered table-hover table-sm">';
    echo '<thead><tr><th>Versión</th><th>Fecha y hora</th><th>Usuario</th><th>Detalle</th></tr></thead>';
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
        echo '<td><button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#' . $detalle_id . '">Ver detalle</button>';
        // Modal Bootstrap para detalle
        echo '<div class="modal fade" id="' . $detalle_id . '" tabindex="-1" aria-labelledby="' . $detalle_id . 'Label" aria-hidden="true">';
        echo '  <div class="modal-dialog modal-lg">';
        echo '    <div class="modal-content">';
        echo '      <div class="modal-header">';
        echo '        <h5 class="modal-title" id="' . $detalle_id . 'Label">Detalle de la versión</h5>';
        echo '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>';
        echo '      </div>';
        echo '      <div class="modal-body">';
        echo '        <strong>Tabla:</strong> ' . esc_html($fila->tabla) . '<br>';
        echo '        <strong>Operación:</strong> ' . esc_html($fila->operacion) . '<br>';
        echo '        <strong>Datos anteriores:</strong><br><textarea readonly style="width:100%;height:80px">' . esc_textarea($fila->datos_anteriores) . '</textarea><br>';
        echo '        <strong>Datos nuevos:</strong><br><textarea readonly style="width:100%;height:80px">' . esc_textarea($fila->datos_nuevos) . '</textarea>';
        echo '      </div>';
        echo '      <div class="modal-footer">';
        echo '        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';
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

// Registrar y cargar Bootstrap solo en las páginas del plugin
add_action('admin_enqueue_scripts', 'bksyncgreen_cargar_bootstrap_admin');
function bksyncgreen_cargar_bootstrap_admin($hook) {
    // Solo cargar en las páginas del plugin
    if (isset($_GET['page']) && $_GET['page'] === 'bksyncgreen') {
        // Bootstrap 5 CDN
        wp_enqueue_style('bksyncgreen-bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css');
        wp_enqueue_script('bksyncgreen-bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', array('jquery'), null, true);
    }
} 